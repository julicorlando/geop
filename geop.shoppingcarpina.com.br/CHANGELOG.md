# Changelog

## 2026-09-22 — V7.5: exclusão de ordens com aprovação

- Gestor e Gestor Síndico podem solicitar exclusão de OS/OL com motivo obrigatório.
- O solicitante não pode aprovar nem reprovar a própria solicitação.
- Outro Gestor, Gestor Síndico ou TI pode aprovar/reprovar.
- Somente TI executa uma exclusão aprovada.
- TI pode excluir diretamente com motivo obrigatório.
- Exclusão lógica (lixeira) em `ordens_servico` e `limpeza_ordens`, sem `DELETE` físico.
- Lixeira exclusiva do TI com restauração.
- Auditoria nativa e notificações para solicitação, decisão e execução.
- Ordens excluídas são ocultadas das principais listagens, detalhes, dashboards, relatórios, rotas e automações.
- Atualizador web idempotente em `public/atualizar_exclusao_ordens_v7_5.php`.
- Pacote de distribuição não inclui `.env` de produção.


## 2026-07-23

- Configuração por ambiente e remoção de credencial/fallback de login fixos.
- Bootstrap seguro, sessões/logs privados, headers, erros e auditoria central.
- Sequência anual transacional de OS e prioridades normalizadas.
- RBAC orientado pelo banco com suporte a `GESTOR_SINDICO`.
- Upload validado por conteúdo e proteção contra execução.
- Chat recente/paginável com CSRF; exportação sem limite artificial.
- Migrations idempotentes do núcleo e dos 18 módulos.
- Crons de preventivas e encerramento CFTV; API IoT autenticada.
- Cache PWA restrito a ativos públicos e WebView endurecida.
- Arquivos operacionais abandonados substituídos por rotas compatíveis.

## 2026-08-03 — Ambientes e QR Code

- Novo módulo de ambientes e rotinas de limpeza por QR Code.
- Página pública com histórico validado e status de higienização.
- Lançamento de ações, fotos, insumos e problemas pelo ASG.
- Validação por supervisor com reprovação e solicitação de correção.
- Integração com OS, anexos, histórico, notificações e solicitação de encerramento.
- Relatórios operacionais e exportação CSV.
- Perfis `ASG` e `SUPERVISOR_ASG` com permissões próprias.

## 2026-08-03 — Limpeza V2 e plano mensal

- Acesso do módulo restrito a Gestor, Supervisor de Limpeza e TI superadministrador.
- Remoção do acesso operacional do perfil ASG ao módulo.
- Importação da planilha com 8 áreas, 34 atividades e 532 marcações de agosto de 2026.
- Cronograma mensal editável com dias, periodicidade, observações e responsáveis.
- Cadastro e edição de áreas e atividades do plano.
- Organograma editável com hierarquia, cargo, setor, turno, contato e vínculo opcional a usuário.
- Registro da limpeza por ambiente com responsável pela execução e usuário que realizou o lançamento.
- Vínculo automático entre registro, atividade do plano e item do cronograma.
- Relatórios por ambiente e responsável, com exportação CSV.
- Página pública atualizada para exibir as atividades validadas sem expor os nomes dos colaboradores.

## 2026-08-03 — Limpeza V3: equipes e múltiplos responsáveis

- Múltiplos responsáveis por item e dia do cronograma.
- Responsável principal opcional, com manutenção dos campos antigos para compatibilidade.
- Cópia mensal preservando as equipes previstas.
- Registro da equipe completa que executou a limpeza por ambiente.
- Participante avulso adicional para terceirizados ou colaboradores ainda não cadastrados.
- Separação entre equipe prevista e equipe efetivamente executora.
- Validação impedida por qualquer integrante da equipe com usuário vinculado.
- Notificações para todos os integrantes da equipe com login no GEOP.
- Relatórios e CSV atualizados para exibir e filtrar toda a equipe.
- Migração automática do responsável único da V2 para responsável principal.
- Nomes da equipe mantidos apenas na área interna, sem exposição na página pública do QR Code.

## 2026-08-03 — Menu Modular V4

- Menu principal reorganizado em Ordens, Limpeza, CFTV, Operações, Admin e Relatório geral.
- Ordens agrupadas em consulta e criação, com Nova OS e Serviço essencial.
- Todas as páginas de limpeza concentradas em um único menu.
- CFTV agrupado por liberações, controle de acesso, relatórios, câmeras offline, ocorrências e configurações.
- Atalhos Câmeras OFF e Ocorrências agora aplicam filtros reais aos relatórios de plantão.
- Operações reúne rota, cronograma, preventivas, estoque, gestão integrada, alertas, conhecimento e patrimônio.
- Admin reúne todas as funções exclusivas do TI, incluindo usuários, perfis, cadastros, token, chat e auditoria.
- Menus e itens continuam condicionados ao perfil e às permissões do usuário.
- Layout do dropdown aprimorado para desktop e celular.

## 2026-09-10 - OL no padrão da OS + 10 relatórios sem token para Gestor
- `public/ol_form.php`: abertura da OL reorganizada para o mesmo padrão visual da abertura de OS, mantendo recursos próprios de limpeza.
- `public/relatorios.php`: Gestor recebe franquia persistente de 10 emissões sem token; filtros e consultas não consomem a franquia.
- `includes/auth.php`: controle/auditoria da franquia de relatórios do Gestor.
- `public/gestor_token.php`: token do TI passa também a renovar a franquia de 10 relatórios.
- `sql/migrations/012_gestor_relatorios_10_sem_token.sql`: tabelas de controle e histórico das emissões.

## 24/09/2026 — GEOP V8.0
- Núcleo de migrations versionadas, preflight, postflight e backup SQL privado.
- Central de aprovações unificada.
- Dashboard financeiro, histórico de custos e fluxo de compras.
- Qualidade da OS, checklists por categoria e detecção de duplicidades.
- IA assistiva local e base segura para futura análise visual.
- Reincidências, relatório mensal, saúde do sistema e integridade de anexos.
- 2FA TOTP opcional, bloqueio temporário de login e expiração por inatividade.
