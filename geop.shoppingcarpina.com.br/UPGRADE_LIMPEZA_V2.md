# Upgrade GEOP — Limpeza V2

## Escopo

Este pacote amplia o módulo de Ambientes/QR Code com o plano mensal extraído da planilha **Plano de Limpeza - Teste Júlio.xlsx**, organograma editável e lançamento das limpezas por ambiente.

O acesso administrativo ao módulo fica restrito a:

- `GESTOR` e `GESTOR_SINDICO`;
- `SUPERVISOR_ASG` (Supervisor de Limpeza);
- `TI`, que permanece como superadministrador do GEOP.

O perfil `ASG` não recebe acesso ao módulo. Os colaboradores da limpeza são cadastrados no organograma e selecionados como responsáveis pelo serviço, com ou sem login próprio no GEOP.

## Dados importados da planilha

- 8 áreas operacionais;
- 34 atividades;
- periodicidade e observações originais;
- 532 marcações de execução para agosto de 2026;
- cronograma mensal editável e reutilizável em outros meses.

Áreas importadas:

1. DOCAS;
2. Corredores de serviços;
3. Praça de alimentação;
4. Administrativo, recepção, salas, PDU, operação e CFTV;
5. Mall;
6. Refeitório;
7. Vestiários, banheiros e fraldários;
8. Estacionamento, Jardim dos Cisnes e área externa das DOCAS.

## Funcionalidades

### Painel da limpeza

- programação do dia;
- tarefas concluídas e pendentes;
- limpezas aguardando validação;
- quantidade de ambientes e integrantes ativos;
- últimos lançamentos.

### Cronograma editável

- navegação por mês e ano;
- edição dos dias programados por atividade;
- responsável previsto por atividade;
- cadastro e edição de áreas;
- cadastro e edição de atividades;
- periodicidade, observação, ordem, exigência de foto e observação;
- cópia das marcações de outro mês;
- vínculo automático com a limpeza registrada no ambiente.

### Organograma editável

- colaborador, cargo, setor, turno e contato;
- superior imediato;
- visualização hierárquica;
- ativação e desativação;
- vínculo opcional com um usuário existente do GEOP;
- colaboradores sem login próprio também podem ser selecionados como responsáveis.

### Ambientes

O Gestor/TI pode vincular cada ambiente a uma área do plano de limpeza. Ao registrar o serviço, o sistema apresenta automaticamente as atividades daquela área.

Gestor ou Supervisor informa:

- ambiente;
- responsável pela execução;
- atividades do plano e ações avulsas;
- início e término;
- observações, problemas e insumos;
- fotos;
- OS relacionada, quando houver.

O sistema grava separadamente:

- quem realizou a limpeza;
- quem lançou a informação no GEOP.

### Validação

- conferência pelo Supervisor/Gestor/TI;
- fotos de inspeção;
- aprovação, reprovação ou correção;
- atualização do cronograma;
- publicação controlada no histórico público;
- solicitação de fechamento quando vinculada a uma OS.

## Instalação

### Sistema que já possui o módulo Ambientes/QR V1

1. Faça backup dos arquivos e do banco.
2. Copie o conteúdo do pacote sobre o GEOP, preservando o `.env`.
3. Importe:

```bash
mysql --default-character-set=utf8mb4 -u USUARIO -p BANCO_GEOP < sql/ATUALIZACAO_MODULO_LIMPEZA_V2.sql
```

### Sistema que ainda não possui o módulo Ambientes/QR

Importe o arquivo completo:

```bash
mysql --default-character-set=utf8mb4 -u USUARIO -p BANCO_GEOP < sql/INSTALACAO_COMPLETA_MODULO_LIMPEZA.sql
```

Depois:

```bash
mkdir -p public/uploads/ambientes
chmod 750 public/uploads/ambientes
```

Saia e entre novamente no GEOP para recarregar as permissões.

## Configuração inicial

1. Acesse **Operação > Painel da limpeza**.
2. Abra **Organograma** e cadastre a equipe.
3. Abra **Cronograma** e defina os responsáveis previstos.
4. Abra **Ambientes e QR Codes** e vincule cada ambiente à área correspondente.
5. Use **Registrar limpeza** para informar o responsável e as tarefas executadas.
6. Use **Validações** para conferir o serviço.

## Observação sobre agosto de 2026

O banco já recebe as marcações encontradas na planilha para agosto de 2026. Ao copiar o cronograma para outro mês, revise os dias porque a cópia mantém os números dos dias e não recalcula automaticamente os dias da semana.
