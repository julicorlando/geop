# GEOP — Menu Modular V4

## Objetivo

Reorganizar o menu principal por área funcional, evitando itens duplicados e mantendo a exibição condicionada às permissões e ao perfil do usuário.

## Estrutura do menu

### Ordens

- Visualizar ordens
- Criar
  - Nova OS
  - Serviço essencial

### Limpeza

- Painel da limpeza
- Cronograma de limpeza
- Organograma da limpeza
- Registrar limpeza
- Ambientes e QR Codes
- Validar limpezas
- Relatórios de limpeza

### CFTV

- Liberações
- Controle de acesso
- Liberações aprovadas
- Histórico de acessos
- Relatórios
- Câmeras OFF
- Ocorrências
- Relatório geral CFTV
- Lojas e destinos

Os atalhos **Câmeras OFF** e **Ocorrências** aplicam filtros reais à listagem de relatórios do CFTV.

### Operações

- Minha rota
- Cronograma de agentes
- Manutenções preventivas
- Estoque
- Gestão integrada
- Alertas operacionais
- Base de conhecimento
- Consultar patrimônio
- Módulos operacionais

### Admin

Exclusivo do TI:

- Usuários
- Perfis e permissões
- Convidar usuário
- Cadastros gerais
- Token do gestor
- Configurar chat
- Auditoria

### Relatório geral

Atalho direto para o relatório consolidado do GEOP, exibido somente aos perfis que já possuíam acesso.

## Compatibilidade

- Não altera tabelas nem dados do banco.
- Compatível com o módulo Limpeza V3.
- Preserva as verificações de perfil e permissões existentes.
- O menu é responsivo e funciona no modo recolhido em celulares.

## Instalação sobre a V3

1. Faça backup dos arquivos atuais.
2. Copie o conteúdo do pacote sobre o GEOP.
3. Preserve o arquivo `.env` do servidor.
4. Não é necessário importar SQL para esta atualização.
5. Saia e entre novamente no sistema para atualizar a sessão e o menu.

## Instalação completa

Caso o módulo Limpeza ainda não esteja instalado, use também:

`sql/INSTALACAO_COMPLETA_MODULO_LIMPEZA_V3.sql`
