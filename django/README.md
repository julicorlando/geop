# GEOP em Django

Reescrita do GEOP (PHP) em **Python 3.12+ / Django 5.2**, com interface nova, permissões configuráveis e
**compressão automática de imagens**. O PHP em `../geop.shoppingcarpina.com.br` continua intacto como
produção e plano de retorno até a virada.

## Módulos

### Núcleo

| Módulo | PHP | Django |
|---|---|---|
| Login, sessão, troca de senha obrigatória | `login.php`, `auth.php`, `mudar_senha.php` | `accounts` (bloqueio após 5 tentativas, sessão expira por inatividade) |
| Usuários, perfis e permissões (RBAC) | `usuarios.php`, `perfis.php` | `accounts` — tudo configurável por tela; nenhuma regra fixa por nome de perfil |
| Locais, categorias, setores | `cadastros.php` | `core` (categorias podem exigir foto antes/durante/depois para encerrar) |
| OS e OL unificadas (V10) | `ordens.php`, `ordem_form.php`, `ordem_detalhe.php` | `ordens` — numeração `OS/OL-AAAA-0000001`, SLA, atribuição (principal + auxiliares), status, aprovação, materiais, checklist, equipe ASG, impressão |
| Reabertura com token do TI | `ordem_detalhe.php` | token de 6 dígitos, 24 h, uso único, guardado como HMAC, limite de tentativas |
| Exclusão com dupla aprovação + lixeira | `exclusao_ordens.php` | solicitante ≠ analista; só o TI executa; restauração |
| Link público da OS | `os-publica.php` | mesmo algoritmo de token (links antigos continuam válidos — ver `GEOP_PUBLIC_SHARE_SECRET`) |
| Ambientes QR (registro e validação de limpeza) | `ambiente_*.php` | `ambientes` — QR público, registro pela equipe, validação do supervisor, fotos publicáveis, cópia das fotos para a OL |
| Organograma e plano de tarefas da limpeza | `limpeza_organograma.php`, plano | `limpeza` |
| Patrimônio vinculado à OS | `patrimonio*.php` | `patrimonio` (banco externo opcional, somente leitura) |
| Notificações e auditoria | `notificacoes.php`, `auditoria.php` | `core` |

### Operação, gestão e integrações

| Módulo | Django | Destaques |
|---|---|---|
| Estoque e compras | `estoque` | saldo e custo médio ponderado, reserva por OS (baixa ao concluir), saída que deixaria o saldo negativo vai para aprovação, solicitação → aprovação (o solicitante não aprova a própria) → compra → recebimento com entrada automática |
| Custos da OS | `estoque` | materiais + mão de obra + terceiros com comprovante (imagem comprimida ou PDF); total final recalculado no encerramento |
| Checklists por categoria | `ordens` | modelos por categoria aplicados automaticamente na abertura; **índice de qualidade** da OS (checklist, fotos exigidas, prazo, reaberturas) |
| Preventivas | `operacao` | geração automática da OS na data, próximo dia útil (feriados), vínculo OS ↔ preventiva |
| Equipe, escalas, cronograma | `operacao` | estado do agente e capacidade máxima de OS ativas, especialidades, escalas, feriados, passagem de turno, cronograma semanal, "minha rota" do dia, recomendação de agente |
| Central de aprovações | `operacao` | tudo o que espera decisão do usuário em uma tela: OS, compras, estoque negativo, exclusões, solicitações de acesso |
| Alertas e SLA | `core` (`executar_rotinas`) | escalonamento de SLA, OS paradas, estoque abaixo do mínimo, preventivas próximas |
| Base de conhecimento | `conhecimento` | artigo gerado a partir da OS encerrada, busca, avaliação "foi útil", sugestões na própria OS |
| Relatórios | `relatorios` | indicadores, agrupamentos, ranking de atraso, evolução mensal, tempo por status, reincidências, financeiro; CSV (abre direto no Excel) e relatório executivo para impressão/PDF com fotos; cota por perfil com liberação por token |
| CFTV | `cftv` | relatório de plantão (diurno/noturno, fechamento automático no horário), ocorrências, observações, liberação de edição por token, confirmação das liberações do dia |
| Controle de acesso | `cftv` | link público de solicitação, aprovação, credencial com QR (mesmo formato do PHP — QR impressos continuam válidos), leitura pela câmera ou leitor USB, entrada/saída, histórico |
| Chat | `chat` | conversas diretas e em grupo, anexos (imagens comprimidas), editar/apagar, não lidas |
| Assistente de IA | `ia` | qualquer API compatível com OpenAI (Groq, OpenAI, Ollama local); responde consultando o próprio GEOP com as **permissões do usuário**; limite diário; funciona sem IA externa com heurísticas locais (duplicidade, prioridade sugerida) |
| IoT e rede | `iot` | API `/iot/api/leituras` (as chaves dos dispositivos do PHP continuam válidas), limites mín./máx. abrem OS automaticamente (uma por incidente), dispositivo offline, monitoramento TCP de câmeras/NVR/switches |
| WhatsApp | `core` | links `wa.me` prontos na OS (solicitante, agente, link público) |
| Segurança | `accounts`, `core` | 2FA (TOTP, segredo criptografado), convite por e-mail para primeiro acesso, recuperação de senha, configurações do sistema, saúde, integridade de arquivos e backups |
| App Android | — | o app atual é um WebView para `https://geop.shoppingcarpina.com.br/` e funciona sem alteração; há manifest/PWA para instalar pelo navegador |

URLs antigas (`/os-publica?id=..&t=..`, `/ambiente_publico?t=..`, `/ordem_detalhe?id=..`, `/acesso_qr`,
`/controle_acesso`, `/api_iot`) redirecionam ou respondem como antes, então **QR Codes já impressos, links
enviados por WhatsApp e sensores instalados continuam funcionando**.

### Diferenças conhecidas em relação ao PHP

* Links públicos de **acompanhamento** de solicitação de acesso enviados antes da virada param de
  funcionar (o PHP só guardava o hash). A credencial com QR continua válida.
* Quem usava 2FA precisa ativá-lo de novo em *Segurança* (o segredo estava cifrado com a chave do PHP).
* Links de solicitação de acesso (`cftv_liberacao_links`) precisam ser gerados de novo.
* Conversas antigas do assistente de IA não são importadas.
* Leitura de QR pela câmera **dentro do app Android** depende de o WebView conceder a permissão de
  câmera; sem isso use o leitor USB ou digite o código.

## Imagens: menores, sem perder as fotos

Toda foto enviada (abertura da OS, anexos, link público, registro e validação de limpeza):

1. **No celular/navegador**, antes do envio, é reduzida para no máximo 1600 px (JPEG 85%). Uma foto de
   câmera de 4–8 MB vira ~0,3–0,6 MB — o upload fica muito mais rápido no 4G. A tela mostra “4,2 MB → 380 KB”.
2. **No servidor**, o arquivo é validado pelo conteúdo (não pela extensão), a rotação EXIF é aplicada,
   os metadados são removidos (inclusive **localização GPS**), e a imagem é regravada em **WEBP**
   (qualidade 75) com uma **miniatura** de 480 px para as galerias, que carregam muito mais rápido.
3. Uma foto nunca fica maior do que chegou: se já estava otimizada e sem metadados, o original é mantido.
4. PDFs são guardados como estão. HEIC recebe uma mensagem explicando como mudar a câmera do iPhone.

Medido com os dados do repositório: as 591 fotos do dump do PHP (já reduzidas para ~1600 px pelo app
antigo) foram de **125,6 MB para 88,1 MB** (−30%), além das miniaturas. Fotos novas, vindas direto da
câmera, encolhem bem mais (tipicamente 90%+).

Ajuste por variáveis de ambiente: `GEOP_IMAGEM_LADO_MAX`, `GEOP_IMAGEM_QUALIDADE`,
`GEOP_IMAGEM_FORMATO` (`WEBP` ou `JPEG`) e `GEOP_UPLOAD_MAX_MB`.

As fotos do PHP referenciadas no banco são **todas importadas e comprimidas** pelo comando de
importação (arquivos órfãos em `public/uploads`, sem registro no banco, não são copiados). Para recomprimir depois: `python manage.py comprimir_imagens --aplicar`.

## Melhorias de segurança em relação ao PHP

- Uploads eram validados só pela extensão e ficavam públicos em `public/uploads`. Agora a mídia é
  **privada** e servida por views que conferem permissão (ou o token público da OS/ambiente).
- O campo `tipo` do anexo era gravado sem validação; agora só valores conhecidos.
- Token de reabertura: limite de tentativas e hash HMAC.
- Bloqueio de login por IP+e-mail, CSRF em todos os formulários (inclusive o link público), HSTS,
  cookies `Secure`/`HttpOnly`, `X-Frame-Options: DENY`.
- Senhas: o hash bcrypt do PHP é aceito e regravado em PBKDF2 no primeiro login — ninguém precisa trocar
  a senha na migração.

> ⚠️ O repositório contém dumps SQL com **hashes de senha, e-mails e telefones reais** e ~950 fotos em
> `public/uploads`. Recomendo removê-los do Git (e do histórico) e trocar as senhas do banco/aplicação.

## Rodar localmente

```sh
cd django
python -m venv .venv && . .venv/bin/activate
pip install -r requirements.txt
cp .env.example .env          # defina DJANGO_DEBUG=true para desenvolvimento
python manage.py migrate      # cria tabelas e os perfis/permissões padrão
python manage.py createsuperuser
python manage.py runserver
python manage.py test         # 52 testes (OS, permissões, QR, compressão, estoque, CFTV, chat, IA, IoT, 2FA…)
```

Ou com Docker: `cp .env.example .env && docker compose up --build`.

## Migrar os dados do PHP

1. Faça backup do MySQL e da pasta `public/uploads`.
2. Em um PostgreSQL novo: `python manage.py migrate`.
3. Importe (o banco do PHP é aberto **somente leitura**):

```sh
LEGACY_DATABASE_URL=mysql://usuario:senha@host:3306/banco \
python manage.py importar_legado --uploads /caminho/geop.shoppingcarpina.com.br/public
```

Importa setores, perfis, usuários (com as senhas), locais, categorias, organograma e plano da limpeza,
preventivas, ordens (IDs e números preservados, custos e qualidade), equipe, histórico, materiais,
checklist, anexos/fotos (comprimidos), patrimônios, exclusões, ambientes QR (mesmos tokens) com registros,
fotos e validações, estoque, movimentações, reservas, compras, comprovantes de custo, modelos de checklist,
equipe/escalas/feriados/passagens de turno/cronograma, base de conhecimento, CFTV (plantões, ocorrências,
liberações, solicitações de acesso com os mesmos IDs, entradas/saídas), chat, dispositivos IoT, leituras e
ativos de rede, notificações não lidas e a auditoria. Tabelas que não existirem na sua versão do PHP são
puladas.

Os materiais do PHP não têm custo unitário: quando a OS tinha `custo_materiais` gravado, o importador cria
uma linha "Ajuste de custo (importado do PHP)" para o valor não se perder num recálculo. Pode ser repetido: o que já existe é ignorado. Ao final mostra quantos arquivos foram
importados e a economia de espaço.

4. Defina `GEOP_PUBLIC_SHARE_SECRET` com o valor de `APP_PUBLIC_SHARE_SECRET` do PHP (se existir) para
   manter os links públicos de OS válidos. Sem ele, gere novos links pela tela da OS.
   Defina `GEOP_QR_SECRET` com o valor de `cftv_acesso_config.qr_secret` para manter as credenciais de
   acesso já emitidas (o importador avisa se forem diferentes).
5. Confira contagens, teste em homologação e só então aponte o domínio para o Django.

## Produção (nginx + gunicorn)

```nginx
location /static/ { alias /app/staticfiles/; }          # ou deixe o WhiteNoise servir
location /protected-media/ { internal; alias /data/media/; }   # com GEOP_X_ACCEL_REDIRECT_PREFIX=/protected-media/
location / {
    proxy_pass http://127.0.0.1:8000;
    proxy_set_header Host $host;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_set_header X-Real-IP $remote_addr;
    client_max_body_size 40m;
}
```

Configure o proxy para que `REMOTE_ADDR` seja o IP real do cliente (usado no bloqueio de login e na
auditoria), por exemplo com `--forwarded-allow-ips` no gunicorn.

## Rotinas agendadas (cron)

```cron
*/5 * * * *  cd /app && python manage.py executar_rotinas          # SLA, preventivas, plantões, IoT offline, alertas
*/10 * * * * cd /app && python manage.py executar_rotinas --somente rede  # teste TCP dos ativos de rede
30 2 * * *   cd /app && python manage.py backup_banco                # pg_dump (ou JSON compactado) em GEOP_BACKUP_DIR
```

Cada execução fica registrada e aparece em *Sistema → Saúde*.

## Assistente de IA

Desligado por padrão. Em *Sistema → Configurações* escolha o provedor e ative; a chave fica só no ambiente:

```sh
GEOP_IA_PROVEDOR=groq            # groq | openai | ollama (outro endpoint compatível: defina GEOP_IA_BASE_URL)
GEOP_IA_API_KEY=...              # não é necessária para Ollama local
# GEOP_IA_BASE_URL=http://localhost:11434/v1
# GEOP_IA_MODELO=llama-3.3-70b-versatile
```

O assistente só enxerga o que o usuário logado pode ver e não altera dados.

## Estrutura

```
config/      settings, urls
accounts/    usuários, perfis, permissões, login (hasher bcrypt do PHP)
core/        locais, categorias, notificações, auditoria, painel, imaging.py (compressão), comandos
ordens/      OS/OL, anexos, histórico, reabertura, exclusão, link público (services.py = regras)
limpeza/     organograma e plano de tarefas
ambientes/   QR, registro e validação de limpeza
patrimonio/  consulta ao banco externo e vínculo com OS
estoque/     materiais, movimentações, reservas, compras, custos da OS
operacao/    preventivas, equipe, escalas, cronograma, aprovações, alertas
conhecimento/ base de conhecimento
relatorios/  indicadores e exportações
cftv/        plantão do CFTV e controle de acesso
chat/        mensagens internas
ia/          assistente (provedor.py, ferramentas.py, heuristica.py)
iot/         sensores e monitoramento de rede
```
