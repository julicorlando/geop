import shutil
import tempfile

from django.core.files.uploadedfile import SimpleUploadedFile
from django.test import TestCase, override_settings
from django.urls import reverse

from accounts.models import Perfil, Usuario
from core.models import Categoria, Local
from core.tests import foto_jpeg

from . import services
from .models import Anexo, Ordem, SolicitacaoExclusao, Status, TokenReabertura

MEDIA = tempfile.mkdtemp()


def criar_usuario(email, perfil, nome=None):
    return Usuario.objects.create_user(email=email, nome=nome or email.split("@")[0].title(), password="Senha-forte-123",
                                       perfil=Perfil.objects.get(codigo=perfil))


@override_settings(MEDIA_ROOT=MEDIA, GEOP_IMAGEM_FORMATO="WEBP")
class OrdensTests(TestCase):
    @classmethod
    def tearDownClass(cls):
        super().tearDownClass()
        shutil.rmtree(MEDIA, ignore_errors=True)

    @classmethod
    def setUpTestData(cls):
        cls.ti = criar_usuario("ti@x.com", "TI")
        cls.gestor = criar_usuario("gestor@x.com", "GESTOR")
        cls.gestor2 = criar_usuario("gestor2@x.com", "GESTOR")
        cls.agente = criar_usuario("agente@x.com", "AGENTE")
        cls.outro_agente = criar_usuario("outro@x.com", "AGENTE")
        cls.local = Local.objects.create(nome="Praça de alimentação")
        cls.cat = Categoria.objects.create(nome="Elétrica", exige_foto_depois=True)
        cls.cat_limpeza = Categoria.objects.create(nome="Limpeza")

    def nova_ordem(self, **extra):
        dados = {"titulo": "Lâmpada queimada", "descricao": "Corredor B", "local": self.local, "categoria": self.cat,
                 "prioridade": "ALTA", "agente": self.agente, **extra}
        return services.criar_ordem(self.gestor, dados)

    def test_numeracao_sequencial_e_tipo_ol(self):
        a, b = self.nova_ordem(), self.nova_ordem()
        self.assertRegex(a.numero, r"^OS-\d{4}-0000001$")
        self.assertTrue(b.numero.endswith("0000002"))
        ol = self.nova_ordem(categoria=self.cat_limpeza)
        self.assertEqual(ol.tipo, "OL")
        self.assertRegex(ol.numero, r"^OL-\d{4}-0000001$")
        self.assertEqual(a.status, Status.ATRIBUIDA)
        self.assertEqual(a.sla_horas, 24)

    def test_criacao_pela_tela_com_fotos_comprimidas(self):
        self.client.force_login(self.gestor)
        foto = SimpleUploadedFile("celular.jpg", foto_jpeg(4000, 3000), content_type="image/jpeg")
        r = self.client.post(reverse("ordens:nova"), {
            "titulo": "Vazamento", "descricao": "Banheiro feminino", "local": self.local.pk, "categoria": self.cat.pk,
            "prioridade": "CRITICA", "fotos": [foto]})
        ordem = Ordem.objects.get(titulo="Vazamento")
        self.assertRedirects(r, ordem.get_absolute_url())
        anexo = ordem.anexos.get()
        self.assertEqual(anexo.tipo, Anexo.Tipo.FOTO_ANTES)
        self.assertEqual(anexo.mime, "image/webp")
        self.assertLessEqual(max(anexo.largura, anexo.altura), 1920)
        self.assertLess(anexo.tamanho, anexo.tamanho_original)
        self.assertTrue(anexo.miniatura.name)
        # o arquivo é servido apenas para quem pode ver a ordem
        self.assertEqual(self.client.get(reverse("ordens:arquivo", args=[anexo.pk])).status_code, 200)
        self.client.force_login(self.outro_agente)
        self.assertEqual(self.client.get(reverse("ordens:arquivo", args=[anexo.pk])).status_code, 403)

    def test_agente_nao_abre_ordem(self):
        self.client.force_login(self.agente)
        self.assertEqual(self.client.get(reverse("ordens:nova")).status_code, 403)

    def test_visibilidade_do_agente(self):
        minha = self.nova_ordem()
        alheia = self.nova_ordem(agente=self.outro_agente)
        visiveis = set(Ordem.objects.visiveis_para(self.agente))
        self.assertIn(minha, visiveis)
        self.assertNotIn(alheia, visiveis)
        self.client.force_login(self.agente)
        self.assertEqual(self.client.get(alheia.get_absolute_url()).status_code, 403)

    def test_fluxo_execucao_aprovacao(self):
        o = self.nova_ordem()
        services.alterar_status(o, self.agente, Status.EM_ANDAMENTO)
        self.assertIsNotNone(o.data_inicio)
        with self.assertRaises(services.PermissionDenied):
            services.alterar_status(o, self.agente, Status.ENCERRADA)
        services.alterar_status(o, self.agente, Status.CONCLUIDA_PELO_AGENTE)
        self.assertTrue(self.gestor.notificacoes.filter(tipo="APROVACAO_CONCLUSAO").exists())
        with self.assertRaisesMessage(services.RegraNegocio, "foto depois"):
            services.alterar_status(o, self.gestor, Status.ENCERRADA)
        services.salvar_arquivos(o, self.agente, [SimpleUploadedFile("d.jpg", foto_jpeg(800, 600))],
                                 tipo=Anexo.Tipo.FOTO_DEPOIS)
        with self.assertRaisesMessage(services.RegraNegocio, "justificativa"):
            services.alterar_status(o, self.gestor, Status.REPROVADA)
        services.alterar_status(o, self.gestor, Status.ENCERRADA, "Ok")
        o.refresh_from_db()
        self.assertEqual(o.status, Status.ENCERRADA)
        self.assertEqual(o.aprovado_por, self.gestor)
        self.assertEqual(o.historico.count(), 4)

    def test_reabertura_com_token(self):
        o = self.nova_ordem(categoria=self.cat_limpeza)
        services.alterar_status(o, self.gestor, Status.ENCERRADA, "fim")
        services.solicitar_token(o, self.gestor, "Serviço refeito")
        token = services.gerar_token(o, self.ti)
        self.assertEqual(len(token), 6)
        self.assertNotIn(token, TokenReabertura.objects.get().token_hash)  # nunca guardado em claro
        with self.assertRaises(services.RegraNegocio):
            services.reabrir_com_token(o, self.gestor, "000000" if token != "000000" else "111111")
        self.assertEqual(TokenReabertura.objects.get().tentativas, 1)
        services.reabrir_com_token(o, self.gestor, token)
        o.refresh_from_db()
        self.assertEqual(o.status, Status.ATRIBUIDA)
        self.assertEqual(o.reabertura_contagem, 1)
        with self.assertRaises(services.RegraNegocio):  # token de uso único
            services.alterar_status(o, self.gestor, Status.ENCERRADA, "fim")
            services.reabrir_com_token(o, self.gestor, token)

    def test_exclusao_com_dupla_aprovacao(self):
        o = self.nova_ordem()
        sol = services.solicitar_exclusao(o, self.gestor, "Duplicada")
        with self.assertRaisesMessage(services.RegraNegocio, "própria"):
            services.analisar_exclusao(sol, self.gestor, True)
        services.analisar_exclusao(sol, self.gestor2, True)
        with self.assertRaises(services.PermissionDenied):
            services.executar_exclusao(sol, self.gestor2)
        services.executar_exclusao(sol, self.ti)
        o.refresh_from_db()
        self.assertIsNotNone(o.excluida_em)
        self.assertNotIn(o, Ordem.objects.visiveis_para(self.ti))
        self.assertEqual(SolicitacaoExclusao.objects.get().estado, "EXECUTADA")
        services.restaurar(o, self.ti)
        self.assertIn(o, Ordem.objects.visiveis_para(self.ti))

    def test_link_publico(self):
        o = self.nova_ordem()
        url = reverse("ordens:publica", args=[o.pk, o.token_publico])
        self.assertEqual(self.client.get(url).status_code, 200)
        self.assertEqual(self.client.get(reverse("ordens:publica", args=[o.pk, "0" * 32])).status_code, 404)
        r = self.client.post(url, {"acao": "fotos", "fotos": [SimpleUploadedFile("p.jpg", foto_jpeg(2000, 1500))]})
        self.assertEqual(r.status_code, 302)
        anexo = o.anexos.get()
        self.assertEqual(anexo.origem, Anexo.Origem.PUBLICO)
        self.assertEqual(self.client.get(reverse("ordens:publica_miniatura", args=[o.pk, o.token_publico, anexo.pk])).status_code, 200)
        self.client.post(url, {"acao": "solicitar_encerramento", "observacao": "Feito"})
        o.refresh_from_db()
        self.assertTrue(o.solicitacao_encerramento)
        # link antigo do PHP redireciona
        self.assertRedirects(self.client.get(f"/os-publica?id={o.pk}&t={o.token_publico}"), url, status_code=301)

    def test_paginas_principais_renderizam(self):
        o = self.nova_ordem()
        services.salvar_arquivos(o, self.gestor, [SimpleUploadedFile("d.jpg", foto_jpeg(300, 300))])
        for user in (self.ti, self.gestor, self.agente):
            self.client.force_login(user)
            for nome, args in [("core:dashboard", []), ("ordens:lista", []), ("ordens:detalhe", [o.pk]),
                               ("ordens:imprimir", [o.pk]), ("core:notificacoes", [])]:
                with self.subTest(user=user.email, pagina=nome):
                    self.assertEqual(self.client.get(reverse(nome, args=args)).status_code, 200)
        self.client.force_login(self.ti)
        for nome, args in [("ordens:exclusoes", []), ("ordens:lixeira", []), ("accounts:usuarios", []),
                           ("accounts:perfis", []), ("core:cadastros", ["locais"]), ("core:auditoria", []),
                           ("accounts:usuario_novo", []), ("accounts:perfil_editar", [self.gestor.perfil_id]),
                           ("ordens:nova", []), ("patrimonio:consultar", []), ("limpeza:organograma", []),
                           ("limpeza:plano", []), ("ambientes:lista", []), ("ambientes:pendentes", []),
                           ("ambientes:relatorios", [])]:
            with self.subTest(pagina=nome):
                self.assertEqual(self.client.get(reverse(nome, args=args)).status_code, 200)

    def test_acoes_pela_tela_de_detalhe(self):
        o = self.nova_ordem()
        self.client.force_login(self.agente)
        url = o.get_absolute_url()
        self.client.post(url, {"acao": "status", "status": "EM_ANDAMENTO", "obs": "Iniciando"})
        self.client.post(url, {"acao": "material", "nome_material": "Lâmpada LED", "quantidade": "2", "unidade": "un",
                               "custo_unitario": "15.50"})
        self.client.post(url, {"acao": "anexo", "tipo": "FOTO_DEPOIS", "descricao": "pronto",
                               "arquivos": [SimpleUploadedFile("x.jpg", foto_jpeg(1000, 800))]})
        self.client.post(url, {"acao": "status", "status": "CONCLUIDA_PELO_AGENTE"})
        self.client.force_login(self.gestor)
        self.client.post(url, {"acao": "status", "status": "ENCERRADA", "obs": "Conferido"})
        o.refresh_from_db()
        self.assertEqual(o.status, Status.ENCERRADA)
        self.assertEqual(str(o.custo_materiais), "31.00")
        self.assertEqual(o.anexos.count(), 1)
