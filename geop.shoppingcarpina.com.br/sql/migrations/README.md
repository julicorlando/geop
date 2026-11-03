# Migrações

Execute os arquivos em ordem numérica, depois de um backup verificado. Requisições web não devem executar migrações.

Executor recomendado: `php bin/migrate.php --yes`.

```sh
mysql --default-character-set=utf8mb4 -u USUARIO -p BANCO < sql/migrations/001_security_foundation.sql
```

Teste primeiro em uma cópia do dump. Use o rollback comentado no arquivo apenas depois de confirmar que as estruturas novas permanecem vazias.

## Módulo de ambientes

A migração `005_ambientes_qr_limpeza.sql` cria o fluxo de QR Code, ASG, supervisão e integração com OS.

## Limpeza V3

A migração `006_limpeza_multiplos_responsaveis.sql` adiciona equipes previstas no cronograma e equipes executoras nos registros por ambiente. Execute-a depois da Limpeza V2.
