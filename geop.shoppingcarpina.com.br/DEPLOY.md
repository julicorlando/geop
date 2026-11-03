# Deploy e rollback

## Atualização

1. Coloque o sistema em manutenção e crie backup lógico:
   `mysqldump --single-transaction --routines --triggers BANCO > backup.sql`.
2. Copie o código para uma nova pasta de release, preservando `.env` e anexos.
3. Importe `sql/ATUALIZACAO.sql`. A conversão geral de collation fica deliberadamente
   separada: audite texto corrompido e então execute `CALL geop_convert_utf8mb4();`.
4. Valide login, upload, criação concorrente de OS, permissões e cron.
5. Troque o symlink/DocumentRoot para a nova release.

## Cron

Execute a cada cinco minutos:

```cron
*/5 * * * * /usr/bin/php /caminho/geop/bin/cron.php all
```

O servidor usa `America/Recife`. Para chamada HTTP, envie `X-Cron-Secret`; CLI é
preferível. Preventivas possuem bloqueio transacional e não duplicam o mesmo ciclo.

## SMTP

Configure as variáveis `SMTP_*` de `.env`. A hospedagem precisa fornecer um relay
autenticado; segredos nunca entram no repositório. O transporte legado `mail()` deve
permanecer desabilitado até a instalação de um adaptador SMTP aprovado.

## Rollback

Reaponte para a release anterior. Se uma alteração de banco impedir compatibilidade,
restaure o dump completo. Não tente desfazer conversão de charset sem backup.
