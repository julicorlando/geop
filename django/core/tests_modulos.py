"""Testes dos módulos portados na segunda etapa (estoque, operação, relatórios, CFTV, chat, IA, IoT, segurança)."""
import json
import shutil
import tempfile
from datetime import datetime, time, timedelta
from decimal import Decimal
from unittest import mock

from django.core.files.uploadedfile import SimpleUploadedFile
from django.core.management import call_command
from django.test import TestCase, override_settings
from django.urls import reverse
from django.utils import timezone

from accounts.models import Perfil, Usuario
from core.models import Categoria, Configuracao, Local
from core.tests import foto_jpeg
from ordens import services as os_services
from ordens.models import ChecklistModelo, ChecklistModeloItem, Ordem, Status

MEDIA = tempfile.mkdtemp()
BACKUPS = tempfile.mkdtemp()


def usuario(email, perfil, **extra):
    return Usuario.objects.create_user(email=email, nome=email.split("@")[0].title(), password="Senha-forte-123",
                                       perfil=Perfil.objects.get(codigo=perfil), **extra)


@override_settings(MEDIA_ROOT=MEDIA, GEOP_BACKUP_DIR=__import__("pathlib").Path(BACKUPS))
class Base(TestCase):
    @classmethod
    def tearDownClass(cls):
        super().tearDownClass()
        shutil.rmtree(MEDIA, ignore_errors=True)
        shutil.rmtree(BACKUPS, ignore_errors=True)

    @classmethod
    def setUpTestData(cls):
        cls.ti = usuario("ti@x.com", "TI")
        cls.gestor = usuario("gestor@x.com", "GESTOR")
        cls.gestor2 = usuario("gestor2@x.com", "GESTOR")
        cls.agente = usuario("agente@x.com", "AGENTE", telefone="81999990000")
        cls.agente2 = usuario("agente2@x.com", "AGENTE")
        cls.cftv = usuario("cftv@x.com", "CFTV")
        cls.local = Local.objects.create(nome="Corredor A")
        cls.cat = Categoria.objects.create(nome="Elétrica")

    def ordem(self, **extra):
        return os_services.criar_ordem(self.gestor, {"titulo": "Troca de lâmpada no corredor", "descricao": "Lâmpada queimada",
                                                     "local": self.local, "categoria": self.cat, "prioridade": "ALTA",
                                                     "agente": self.agente, **extra})


class EstoqueTests(Base):
    def test_consumo_reserva_e_saldo_negativo_com_aprovacao(self):
        from estoque.models import MaterialEstoque, Reserva
        from estoque.services import decidir_negativo

        mat = MaterialEstoque.objects.create(nome="Lâmpada LED", unidade="un", quantidade_atual=5, custo_unitario=10)
        o = self.ordem()
        os_services.registrar_material(o, self.agente, "", 2, material_estoque=mat)
        mat.refresh_from_db()
        o.refresh_from_db()
        self.assertEqual(mat.quantidade_atual, 3)
        self.assertEqual(o.custo_materiais, Decimal("20.00"))
        os_services.registrar_material(o, self.agente, "", 2, material_estoque=mat, reservar=True)
        self.assertEqual(mat.disponivel, 1)
        item = os_services.registrar_material(o, self.agente, "", 3, material_estoque=mat)
        self.assertEqual(item.situacao, "AGUARDANDO_APROVACAO")
        self.assertTrue(self.gestor.notificacoes.filter(tipo="MATERIAL_APROVACAO").exists())
        reserva = Reserva.objects.get(status=Reserva.Status.APROVACAO_NEGATIVO)
        decidir_negativo(reserva, self.gestor, True, "urgente")
        mat.refresh_from_db()
        self.assertEqual(mat.quantidade_atual, 0)  # 3 - 3 consumidos; a reserva de 2 continua pendente
        # ao encerrar, reservas pendentes são consumidas e o custo consolidado
        os_services.salvar_arquivos(o, self.agente, [SimpleUploadedFile("d.jpg", foto_jpeg(400, 300))], tipo="FOTO_DEPOIS")
        os_services.alterar_status(o, self.gestor, Status.ENCERRADA, "Concluído com sucesso")
        mat.refresh_from_db()
        o.refresh_from_db()
        self.assertEqual(mat.quantidade_atual, -2)
        self.assertEqual(o.custo_materiais, Decimal("70.00"))
        self.assertEqual(o.custo_total_final, Decimal("70.00"))

    def test_compra_fluxo_completo(self):
        from estoque.models import Compra, MaterialEstoque
        from estoque.services import ErroEstoque, criar_compra, decidir_compra, receber_compra, registrar_compra

        mat = MaterialEstoque.objects.create(nome="Cabo 2,5mm", unidade="m", quantidade_atual=0)
        c = criar_compra(self.gestor, [{"descricao": "Cabo", "material": mat, "quantidade": Decimal(100),
                                        "custo_unitario": Decimal("2.50")}], justificativa="Reposição")
        self.assertEqual(c.valor_previsto, Decimal("250.00"))
        with self.assertRaises(ErroEstoque):
            decidir_compra(c, self.gestor, True)  # não aprova a própria
        decidir_compra(c, self.gestor2, True)
        registrar_compra(c, self.gestor2, "Fornecedor X", "NF 123", Decimal("240"))
        receber_compra(c, self.gestor2)
        mat.refresh_from_db()
        self.assertEqual(mat.quantidade_atual, 100)
        self.assertEqual(mat.custo_unitario, Decimal("2.40"))
        self.assertEqual(Compra.objects.get().status, "RECEBIDA")

    def test_custos_e_paginas(self):
        o = self.ordem()
        self.client.force_login(self.gestor)
        url = reverse("estoque:custos", args=[o.pk])
        self.client.post(url, {"tipo": "MAO_OBRA", "valor": "150", "descricao": "Eletricista",
                               "comprovante": SimpleUploadedFile("nf.pdf", b"%PDF-1.4 teste", "application/pdf")})
        self.client.post(url, {"tipo": "TERCEIRO", "valor": "50"})
        o.refresh_from_db()
        self.assertEqual(o.custo_mao_obra, Decimal("150"))
        self.assertEqual(o.custo_total_final, Decimal("200"))
        custo = o.custos.get(tipo="MAO_OBRA")
        self.assertEqual(self.client.get(reverse("estoque:custo_arquivo", args=[custo.pk])).status_code, 200)
        for nome in ("estoque:lista", "estoque:compras", "estoque:movimentacoes"):
            self.assertEqual(self.client.get(reverse(nome)).status_code, 200)


class OperacaoTests(Base):
    def test_checklist_da_categoria_e_qualidade(self):
        m = ChecklistModelo.objects.create(categoria=self.cat, nome="Padrão elétrica")
        ChecklistModeloItem.objects.create(modelo=m, descricao="Desligar disjuntor", obrigatorio=True)
        o = self.ordem()
        self.assertEqual(o.checklist.count(), 1)
        with self.assertRaisesMessage(os_services.RegraNegocio, "checklist"):
            os_services.alterar_status(o, self.gestor, Status.ENCERRADA)
        pct, itens = os_services.calcular_qualidade(o)
        self.assertTrue(0 < pct < 100)

    def test_capacidade_e_recomendacao(self):
        from operacao.models import EquipeEstado, Especialidade
        from operacao.services import recomendar_agentes

        EquipeEstado.objects.create(usuario=self.agente, capacidade_maxima=1)
        self.ordem()
        with self.assertRaisesMessage(os_services.RegraNegocio, "capacidade"):
            self.ordem()
        Especialidade.objects.create(usuario=self.agente2, especialidade="Elétrica", nivel=3)
        rec = recomendar_agentes(self.cat)
        self.assertEqual(rec[0]["id"], self.agente2.pk)
        self.assertNotIn(self.agente.pk, [r["id"] for r in rec])  # lotado
        EquipeEstado.objects.create(usuario=self.agente2, estado="FERIAS")
        self.assertEqual(recomendar_agentes(self.cat), [])

    def test_preventiva_gera_ordem_e_avanca_data(self):
        from operacao.models import Preventiva
        from operacao.services import gerar_preventivas

        p = Preventiva.objects.create(titulo="Revisar quadro elétrico", local=self.local, categoria=self.cat,
                                      intervalo_dias=30, proxima_execucao=timezone.localdate(), criado_por=self.gestor)
        r = gerar_preventivas()
        self.assertEqual(r["criadas"], 1)
        o = Ordem.objects.get(preventiva=p)
        self.assertEqual(o.origem, "PREVENTIVA")
        p.refresh_from_db()
        self.assertGreaterEqual(p.proxima_execucao, timezone.localdate() + timedelta(days=30))
        self.assertEqual(gerar_preventivas()["criadas"], 0)

    def test_rotinas_e_paginas(self):
        o = self.ordem()
        Ordem.objects.filter(pk=o.pk).update(prazo=timezone.now() - timedelta(hours=1))
        call_command("executar_rotinas", verbosity=0, stdout=open("/dev/null", "w"))
        self.assertTrue(self.gestor.notificacoes.filter(tipo="SLA_VENCIDA").exists())
        from core.models import ExecucaoRotina

        self.assertEqual(ExecucaoRotina.objects.get(comando="executar_rotinas").status, "OK")
        self.client.force_login(self.gestor)
        for nome in ("operacao:preventivas", "operacao:equipe", "operacao:escalas", "operacao:passagens",
                     "operacao:cronograma", "operacao:servico_essencial", "operacao:aprovacoes", "ordens:checklists"):
            with self.subTest(nome):
                self.assertEqual(self.client.get(reverse(nome)).status_code, 200)
        self.client.force_login(self.agente)
        self.assertContains(self.client.get(reverse("operacao:minha_rota")), o.numero)

    def test_servico_essencial_atribui_recomendado(self):
        self.client.force_login(self.gestor)
        r = self.client.post(reverse("operacao:servico_essencial"), {"titulo": "Disjuntor desarmado", "descricao": "Loja sem energia",
                                                                     "local": self.local.pk, "categoria": self.cat.pk})
        o = Ordem.objects.get(origem="ESSENCIAL")
        self.assertRedirects(r, o.get_absolute_url())
        self.assertEqual(o.prioridade, "CRITICA")
        self.assertIsNotNone(o.agente)

    def test_duplicidade_bloqueia_ate_confirmar(self):
        self.ordem()
        self.client.force_login(self.gestor)
        dados = {"titulo": "Troca de lâmpada no corredor", "descricao": "Lâmpada queimada", "local": self.local.pk,
                 "categoria": self.cat.pk, "prioridade": "MEDIA"}
        self.assertContains(self.client.post(reverse("ordens:nova"), dados), "Possíveis duplicidades")
        self.client.post(reverse("ordens:nova"), {**dados, "ignorar_duplicidade": "on"})
        self.assertEqual(Ordem.objects.count(), 2)
        self.assertIsNotNone(Ordem.objects.latest("pk").duplicidade_referencia)


class ConhecimentoRelatoriosTests(Base):
    def test_artigo_gerado_ao_encerrar(self):
        from conhecimento.models import Artigo

        o = self.ordem()
        os_services.salvar_arquivos(o, self.agente, [SimpleUploadedFile("d.jpg", foto_jpeg(300, 300))], tipo="FOTO_DEPOIS")
        os_services.alterar_status(o, self.gestor, Status.ENCERRADA, "Substituído reator e lâmpada LED 18W")
        a = Artigo.objects.get(origem_ordem=o)
        self.client.force_login(self.agente)
        self.assertContains(self.client.get(reverse("conhecimento:lista") + "?q=reator"), a.titulo)
        o2 = self.ordem(titulo="Lâmpada piscando no corredor")
        from conhecimento.services import sugestoes

        self.assertIn(a, sugestoes(o2))

    def test_relatorios_csv_e_franquia_do_gestor(self):
        self.ordem()
        Configuracao.definir("relatorios_limite_gestor", "1")
        self.client.force_login(self.gestor)
        for nome in ("relatorios:ordens", "relatorios:mensal", "relatorios:financeiro", "relatorios:reincidencias"):
            self.assertEqual(self.client.get(reverse(nome)).status_code, 200)
        r = self.client.get(reverse("relatorios:ordens") + "?exportar=csv")
        self.assertEqual(r.status_code, 200)
        self.assertIn("Troca de lâmpada", r.content.decode("utf-8-sig"))
        r = self.client.get(reverse("relatorios:ordens") + "?exportar=csv")
        self.assertRedirects(r, reverse("relatorios:token") + "?voltar=/relatorios/%3Fexportar%3Dcsv", fetch_redirect_response=False)
        from relatorios.services import gerar_token

        token = gerar_token(self.ti, self.gestor)
        self.client.post(reverse("relatorios:token"), {"token": token, "voltar": "/relatorios/"})
        self.assertEqual(self.client.get(reverse("relatorios:ordens") + "?exportar=executivo").status_code, 200)
        self.client.force_login(self.ti)  # TI não tem franquia
        for _ in range(3):
            self.assertEqual(self.client.get(reverse("relatorios:ordens") + "?exportar=csv").status_code, 200)


class CFTVTests(Base):
    def test_plantao_token_de_edicao(self):
        from cftv import services
        from cftv.models import RelatorioPlantao

        rel, _ = services.criar_plantao(self.cftv, timezone.localdate() - timedelta(days=1), "DIURNO")
        self.assertFalse(services.pode_editar_plantao(self.cftv, rel))
        token = services.gerar_token_edicao(rel, self.ti)
        services.usar_token_edicao(rel, self.cftv, token)
        self.assertTrue(services.pode_editar_plantao(self.cftv, rel))
        self.assertEqual(services.fechar_plantoes_vencidos(), 1)
        self.assertEqual(RelatorioPlantao.objects.get().status, "FINALIZADO")
        self.client.force_login(self.cftv)
        self.assertEqual(self.client.get(rel.get_absolute_url()).status_code, 200)
        self.client.post(rel.get_absolute_url(), {"acao": "ocorrencia", "nivel": "NIVEL_3", "tipo": "Furto", "local": "Loja 10"})
        self.assertEqual(rel.ocorrencias.count(), 1)

    def test_solicitacao_publica_aprovacao_e_portaria(self):
        from cftv import services
        from cftv.models import EventoAcesso, LinkSolicitacao, SolicitacaoAcesso

        link = LinkSolicitacao.objects.create(expira_em=timezone.now() + timedelta(days=1), criado_por=self.gestor)
        hoje = timezone.localdate()
        dados = {"nome_completo": "Fulano de Tal", "cpf": "529.982.247-25", "destino_tipo": "LOJA_MALL",
                 "destino_nome": "Loja 10", "data_acesso": hoje.isoformat(), "motivo": "Manutenção da vitrine",
                 "acompanhantes": "Beltrano; 111.444.777-35", "aceite": "on"}
        r = self.client.post(reverse("cftv:solicitar", args=[link.token]), {**dados, "cpf": "123.456.789-00"})
        self.assertContains(r, "CPF inválido")
        r = self.client.post(reverse("cftv:solicitar", args=[link.token]), dados)
        sol = SolicitacaoAcesso.objects.get()
        self.assertRedirects(r, reverse("cftv:acompanhar", args=[sol.acompanhamento]))
        self.assertEqual(sol.pessoas.count(), 1)
        # link de uso único
        self.assertEqual(self.client.get(reverse("cftv:solicitar", args=[link.token])).status_code, 404)
        self.assertContains(self.client.get(reverse("cftv:acompanhar", args=[sol.acompanhamento])), "Aguardando")
        with self.assertRaises(services.ErroCFTV):
            services.decidir(sol, self.gestor, False)  # reprovação sem justificativa
        services.decidir(sol, self.gestor, True)
        sol.refresh_from_db()
        self.assertIsNotNone(sol.liberacao)
        self.assertContains(self.client.get(reverse("cftv:credencial") + f"?codigo={sol.codigo_qr}"), "Acesso aprovado")
        self.assertEqual(self.client.get(reverse("cftv:credencial") + f"?codigo={sol.pk}.{'0' * 64}").status_code, 404)
        # portaria: 1ª leitura = entrada; repetida em < 2 min é ignorada; depois = saída; depois bloqueia
        meio_dia = timezone.make_aware(datetime.combine(hoje, time(12, 0)))
        ev, _ = services.registrar_evento(sol, self.cftv, agora=meio_dia)
        self.assertEqual(ev.tipo, EventoAcesso.Tipo.ENTRADA)
        ev, msg = services.registrar_evento(sol, self.cftv, agora=meio_dia + timedelta(seconds=30))
        self.assertIsNone(ev)
        ev, _ = services.registrar_evento(sol, self.cftv, agora=meio_dia + timedelta(hours=2))
        self.assertEqual(ev.tipo, EventoAcesso.Tipo.SAIDA)
        with self.assertRaisesMessage(services.ErroCFTV, "finalizado"):
            services.registrar_evento(sol, self.cftv, agora=meio_dia + timedelta(hours=3))
        self.assertTrue(sol.voucher_utilizado(hoje))
        # tela da portaria com o QR lido como URL
        self.client.force_login(self.cftv)
        self.assertEqual(self.client.get(reverse("cftv:controle")).status_code, 200)
        for nome in ("cftv:painel", "cftv:historico"):
            self.assertEqual(self.client.get(reverse(nome)).status_code, 200)
        self.client.force_login(self.gestor)
        self.assertEqual(self.client.get(reverse("cftv:liberacoes")).status_code, 200)
        self.assertRedirects(self.client.get(f"/acesso_qr?codigo={sol.codigo_qr}"),
                             reverse("cftv:credencial") + f"?codigo={sol.codigo_qr}", fetch_redirect_response=False)

    def test_entrada_respeita_horario(self):
        from cftv import services
        from cftv.models import SolicitacaoAcesso

        hoje = timezone.localdate()
        sol = SolicitacaoAcesso.objects.create(protocolo="AC-1", nome_completo="X", cpf="52998224725", data_acesso=hoje,
                                               hora_inicio=time(14, 0), hora_fim=time(16, 0), destino_nome="Loja",
                                               status="APROVADA")
        with self.assertRaisesMessage(services.ErroCFTV, "uma hora antes"):
            services.registrar_evento(sol, self.cftv, agora=timezone.make_aware(datetime.combine(hoje, time(12, 30))))
        ev, _ = services.registrar_evento(sol, self.cftv, agora=timezone.make_aware(datetime.combine(hoje, time(13, 15))))
        self.assertIsNotNone(ev)


class ChatIATests(Base):
    def test_chat_direto(self):
        self.client.force_login(self.agente)
        r = self.client.post(reverse("chat:inicio"), {"usuario": self.gestor.pk})
        conv_url = r["Location"]
        pk = int(conv_url.strip("/").split("/")[-1])
        r = self.client.post(reverse("chat:api_enviar", args=[pk]), {"texto": "Bom dia, chefe"})
        self.assertEqual(r.status_code, 200)
        self.client.post(reverse("chat:api_enviar", args=[pk]), {"arquivo": SimpleUploadedFile("f.jpg", foto_jpeg(2500, 2000))})
        self.client.force_login(self.gestor)
        j = self.client.get(reverse("chat:api_conversas")).json()
        self.assertEqual(j["total_nao_lidas"], 2)
        msgs = self.client.get(reverse("chat:api_mensagens", args=[pk])).json()["mensagens"]
        self.assertEqual(msgs[0]["texto"], "Bom dia, chefe")
        self.assertEqual(self.client.get(msgs[1]["miniatura"]).status_code, 200)
        self.assertEqual(self.client.get(reverse("chat:api_conversas")).json()["total_nao_lidas"], 0)
        self.client.force_login(self.agente2)  # não participa
        self.assertEqual(self.client.get(reverse("chat:api_mensagens", args=[pk])).status_code, 403)

    def test_ferramentas_respeitam_permissoes(self):
        from ia import ferramentas

        self.ordem()
        self.assertEqual(ferramentas.executar(self.gestor, "buscar_ordens", {"texto": "lâmpada"})["total"], 1)
        self.assertEqual(ferramentas.executar(self.agente2, "buscar_ordens", {"texto": "lâmpada"})["total"], 0)
        self.assertIn("erro", ferramentas.executar(self.agente, "consultar_custos", {}))
        self.assertIn("totais", ferramentas.executar(self.gestor, "consultar_custos", {}))

    def test_provedor_com_chamada_de_ferramenta(self):
        from ia import provedor

        Configuracao.definir("ia_habilitada", "1")
        o = self.ordem()
        respostas = [
            {"choices": [{"message": {"content": "", "tool_calls": [{"id": "1", "function": {
                "name": "resumir_ordem", "arguments": json.dumps({"numero": o.numero})}}]}}]},
            {"choices": [{"message": {"content": f"A {o.numero} está atribuída."}}]},
        ]
        with mock.patch.dict("os.environ", {"GEOP_IA_API_KEY": "x"}), \
                mock.patch.object(provedor, "_post", side_effect=respostas) as post:
            self.client.force_login(self.gestor)
            r = self.client.post(reverse("ia:chat"), {"pergunta": f"Como está a {o.numero}?"}, follow=True)
        self.assertContains(r, "está atribuída")
        self.assertEqual(post.call_count, 2)
        tool_msg = post.call_args_list[1].args[1]["messages"][-1]
        self.assertIn("Troca de lâmpada", tool_msg["content"])

    def test_ia_desabilitada_explica(self):
        self.client.force_login(self.gestor)
        self.assertContains(self.client.get(reverse("ia:chat")), "desativado")
        r = self.client.get(reverse("ordens:assistente") + "?titulo=Vazamento grande&descricao=alagamento no banheiro")
        self.assertEqual(r.json()["resultado"]["prioridade_sugerida"], "CRITICA")


class IoTSegurancaTests(Base):
    def test_api_iot_abre_ordem_uma_vez(self):
        from iot.models import Dispositivo

        d = Dispositivo(identificador="temp-cozinha", tipo="temperatura", local=self.local, categoria_os=self.cat,
                        limite_max=Decimal("8"))
        chave = d.nova_chave()
        d.save()
        url = reverse("iot:api_leituras")
        self.assertEqual(self.client.post(url, "{}", content_type="application/json").status_code, 401)
        self.assertEqual(self.client.post(url, "{}", content_type="application/json", HTTP_X_API_KEY="errada").status_code, 403)
        r = self.client.post(url, json.dumps({"valor": 5}), content_type="application/json", HTTP_X_API_KEY=chave)
        self.assertEqual(r.json()["alertas"], 0)
        for _ in range(2):
            r = self.client.post(url, json.dumps({"valor": 12.5}), content_type="application/json", HTTP_X_API_KEY=chave)
        self.assertEqual(Ordem.objects.filter(origem="IOT").count(), 1)
        d.refresh_from_db()
        self.assertEqual(d.status, "ALERTA")

    def test_rede_falhas_consecutivas(self):
        from iot.models import AtivoRede
        from iot.services import verificar_rede

        a = AtivoRede.objects.create(tipo="NVR", nome="NVR 1", endereco_ip="10.0.0.9", local=self.local, falhas_para_alerta=2)
        falha = lambda h, p: (False, None)  # noqa: E731
        verificar_rede(falha)
        a.refresh_from_db()
        self.assertEqual(a.status, "INSTAVEL")
        verificar_rede(falha)
        verificar_rede(falha)
        a.refresh_from_db()
        self.assertEqual(a.status, "FALHA")
        self.assertEqual(Ordem.objects.filter(origem="MONITORAMENTO").count(), 1)
        verificar_rede(lambda h, p: (True, 5))
        a.refresh_from_db()
        self.assertEqual(a.status, "ONLINE")

    def test_login_com_2fa(self):
        from accounts import totp

        segredo = totp.novo_segredo()
        self.gestor.totp_segredo, self.gestor.totp_ativo = totp.cifrar(segredo), True
        self.gestor.save()
        r = self.client.post(reverse("accounts:login"), {"username": "gestor@x.com", "password": "Senha-forte-123"})
        self.assertRedirects(r, reverse("accounts:dois_fatores"))
        self.assertEqual(self.client.get(reverse("core:dashboard")).status_code, 302)  # ainda não logado
        self.assertContains(self.client.post(reverse("accounts:dois_fatores"), {"codigo": "000000"}), "inválido")
        r = self.client.post(reverse("accounts:dois_fatores"), {"codigo": totp.codigo(segredo)})
        self.assertEqual(self.client.get(reverse("core:dashboard")).status_code, 200)

    def test_convite_define_senha(self):
        self.client.force_login(self.ti)
        r = self.client.post(reverse("accounts:usuario_novo"), {
            "nome": "Novo Agente", "email": "novo@x.com", "perfil": Perfil.objects.get(codigo="AGENTE").pk,
            "is_active": "on", "enviar_convite": "on"})
        link = r.context["link"]
        novo = Usuario.objects.get(email="novo@x.com")
        self.assertFalse(novo.has_usable_password())
        self.client.logout()
        caminho = link.split("testserver")[-1]
        self.client.post(caminho, {"new_password1": "Outra-senha-forte-9", "new_password2": "Outra-senha-forte-9"})
        self.assertTrue(self.client.login(username="novo@x.com", password="Outra-senha-forte-9"))
        self.assertEqual(self.client.get(caminho).status_code, 404)  # uso único

    def test_backup_e_paginas_do_sistema(self):
        call_command("backup_banco", stdout=open("/dev/null", "w"))
        self.client.force_login(self.ti)
        r = self.client.get(reverse("core:backups"))
        self.assertContains(r, ".json.gz")
        for nome in ("core:saude", "core:arquivos", "core:configuracoes", "ia:configuracao", "iot:painel",
                     "relatorios:token", "accounts:seguranca", "chat:inicio", "ia:chat", "ia:assistente",
                     "conhecimento:lista", "core:dashboard", "core:notificacoes_api", "core:manifest", "core:sw"):
            with self.subTest(nome):
                self.assertEqual(self.client.get(reverse(nome)).status_code, 200)
        self.client.force_login(self.gestor)
        self.assertEqual(self.client.get(reverse("core:backups")).status_code, 403)
