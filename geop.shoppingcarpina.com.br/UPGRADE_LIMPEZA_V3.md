# Upgrade GEOP — Limpeza V3

## Objetivo

A V3 adiciona múltiplos responsáveis ao cronograma e aos registros de limpeza por ambiente, mantendo compatibilidade com os dados da V2.

O sistema passa a separar:

- **equipe prevista**: integrantes programados para determinada atividade e dia;
- **responsável principal previsto**: referência principal da equipe no cronograma;
- **equipe executora**: todas as pessoas que realmente participaram da limpeza;
- **responsável principal da execução**: integrante usado para compatibilidade com campos antigos;
- **usuário que lançou no GEOP**: Gestor, Supervisor ou TI que registrou o serviço.

## Funcionalidades da V3

### Cronograma

- seleção de vários integrantes por atividade;
- responsável principal opcional;
- equipe gravada separadamente em cada dia programado;
- exibição da equipe ao passar o cursor sobre o dia;
- cópia de mês preservando dias e equipes;
- dias concluídos não são alterados ao salvar novamente a linha;
- dados antigos com um único responsável são migrados automaticamente.

### Registro por ambiente

- seleção de todos os colaboradores que participaram;
- indicação de um integrante principal;
- inclusão de participante avulso não cadastrado;
- equipe prevista exibida nas atividades programadas do dia;
- equipe executora armazenada separadamente do planejamento;
- histórico, validação, notificações e relatórios exibem toda a equipe.

### Validação e segurança

- qualquer integrante da equipe com usuário vinculado fica impedido de validar o próprio serviço;
- aprovação ou correção pode notificar todos os integrantes com login no GEOP;
- OS vinculada recebe a equipe completa na observação de solicitação de encerramento;
- nomes da equipe continuam restritos à área interna e aos relatórios; a página pública do QR Code não expõe os colaboradores.

### Relatórios

- coluna **Equipe responsável**;
- filtro encontra registros em que o colaborador participou, mesmo quando não foi o principal;
- exportação CSV com todos os integrantes da execução.

## Banco de dados

A atualização cria:

```text
limpeza_cronograma_responsaveis
ambiente_registro_responsaveis
```

Os campos únicos antigos permanecem para compatibilidade. Ao importar a migração:

- `limpeza_cronograma_itens.responsavel_membro_id` vira o principal da equipe prevista;
- `ambiente_registros.executor_membro_id` e `executor_nome` viram o principal da equipe executora;
- os demais integrantes passam a ser armazenados nas novas tabelas de relacionamento.

## Instalação sobre a V2

```bash
mysql --default-character-set=utf8mb4 -u USUARIO -p BANCO_GEOP < sql/ATUALIZACAO_MODULO_LIMPEZA_V3.sql
```

Depois copie os arquivos do pacote sobre o GEOP, preservando o `.env`.

## Instalação completa

Para uma instalação que ainda não recebeu Ambientes/QR e Limpeza V2:

```bash
mysql --default-character-set=utf8mb4 -u USUARIO -p BANCO_GEOP < sql/INSTALACAO_COMPLETA_MODULO_LIMPEZA_V3.sql
```

Depois:

```bash
mkdir -p public/uploads/ambientes
chmod 750 public/uploads/ambientes
```

Saia e entre novamente no GEOP para recarregar as permissões.

## Uso

1. Cadastre os integrantes em **Limpeza > Organograma**.
2. Abra **Limpeza > Cronograma**.
3. Em cada atividade, selecione vários integrantes.
4. Escolha o responsável principal.
5. Marque os dias e salve.
6. No lançamento do ambiente, marque a equipe que realmente executou.
7. O Supervisor valida o registro normalmente.

## Compatibilidade

A migração é incremental e não remove colunas ou registros da V2. Antes da implantação em produção, faça backup e execute primeiro em uma cópia de homologação.
