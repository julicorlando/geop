import shutil
import tempfile

from django.core.files.uploadedfile import SimpleUploadedFile
from django.test import TestCase, override_settings
from django.urls import reverse

from core.models import Categoria, Local
from core.tests import foto_jpeg
from ordens import services as os_services
from ordens.tests import criar_usuario

from .models import Ambiente, AmbienteAcao, AmbienteAcaoVinculo, Registro

MEDIA = tempfile.mkdtemp()


@override_settings(MEDIA_ROOT=MEDIA)
class AmbientesTests(TestCase):
    @classmethod
    def tearDownClass(cls):
        super().tearDownClass()
        shutil.rmtree(MEDIA, ignore_errors=True)

    @classmethod
    def setUpTestData(cls):
        cls.gestor = criar_usuario("g@x.com", "GESTOR")
        cls.asg = criar_usuario("asg@x.com", "ASG")
        cls.sup = criar_usuario("sup@x.com", "SUPERVISOR_ASG")
        cls.local = Local.objects.create(nome="Banheiro piso 1")
        cls.amb = Ambiente.objects.create(local=cls.local, nome_publico="Banheiro feminino", exibir_fotos=True)
        cls.acao = AmbienteAcao.objects.create(nome="Repor papel")
        AmbienteAcaoVinculo.objects.create(ambiente=cls.amb, acao=cls.acao, obrigatoria=True)
        cls.ordem = os_services.criar_ordem(cls.gestor, {
            "titulo": "Limpeza", "descricao": "x", "local": cls.local, "categoria": Categoria.objects.create(nome="Limpeza"),
            "prioridade": "MEDIA"})

    def test_fluxo_qr_registro_validacao(self):
        publico = reverse("ambientes:publico", args=[self.amb.token_publico])
        r = self.client.get(publico)
        self.assertContains(r, "Sem limpeza validada")
        self.client.force_login(self.asg)
        url = reverse("ambientes:registro", args=[self.amb.token_publico])
        self.assertEqual(self.client.get(url).status_code, 200)
        r = self.client.post(url, {"observacao_asg": "ok"})
        self.assertContains(r, "obrigatórias")
        r = self.client.post(url, {"acoes": [self.acao.pk], "ordem": self.ordem.pk,
                                   "fotos": [SimpleUploadedFile("a.jpg", foto_jpeg(3000, 2000))]})
        self.assertRedirects(r, publico)
        reg = Registro.objects.get()
        self.assertEqual(reg.fotos.get().largura, 1600)
        self.assertTrue(self.sup.notificacoes.filter(tipo="LIMPEZA_VALIDAR").exists())

        self.client.force_login(self.sup)
        v = reverse("ambientes:validar", args=[reg.pk])
        self.assertEqual(self.client.get(v).status_code, 200)
        r = self.client.post(v, {"resultado": "APROVADA"})
        self.assertContains(r, "exige foto de inspeção")
        r = self.client.post(v, {"resultado": "APROVADA", "publicar_fotos": "on",
                                 "fotos": [SimpleUploadedFile("b.jpg", foto_jpeg(800, 600))]})
        self.assertRedirects(r, reverse("ambientes:pendentes"))
        reg.refresh_from_db()
        self.assertEqual(reg.status, Registro.Status.VALIDADA)
        # fotos copiadas para a OL (mesmo arquivo, sem duplicar) e encerramento solicitado
        self.ordem.refresh_from_db()
        self.assertTrue(self.ordem.solicitacao_encerramento)
        self.assertEqual(self.ordem.anexos.count(), 2)
        self.assertEqual(set(self.ordem.anexos.values_list("arquivo", flat=True)),
                         set(reg.fotos.values_list("arquivo", flat=True)))
        self.client.logout()
        r = self.client.get(publico)
        self.assertContains(r, "Ambiente higienizado")
        foto = reg.fotos.first()
        self.assertEqual(self.client.get(reverse("ambientes:foto_publica_mini", args=[self.amb.token_publico, foto.pk])).status_code, 200)
        # apagar o anexo da OS não pode apagar o arquivo usado pelo registro
        anexo = self.ordem.anexos.first()
        anexo.delete()
        anexo.remover_arquivos()
        self.assertTrue(foto.arquivo.storage.exists(foto.arquivo.name) or foto.arquivo.name != anexo.arquivo.name)

    def test_quem_registra_nao_valida(self):
        reg = Registro.objects.create(ambiente=self.amb, asg=self.sup)
        with self.assertRaisesMessage(os_services.RegraNegocio, "não pode validá-lo"):
            from .services import validar
            validar(reg, self.sup, "APROVADA", "", [])

    def test_qr_legado_redireciona(self):
        r = self.client.get(f"/ambiente_publico?t={self.amb.token_publico}")
        self.assertRedirects(r, reverse("ambientes:publico", args=[self.amb.token_publico]), status_code=301)
