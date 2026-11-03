# Arquivos fora da pasta pública

Devem permanecer fora do DocumentRoot e do ZIP de produção:

- `.env`, `var/`, `tmp/`, logs e sessões;
- `sql/`, migrations, dumps e backups;
- `android/build`, `android/dist`, keystores, APK/AAB de teste;
- documentação interna, caches e arquivos temporários.

Somente `public/` deve receber acesso web direto. `public/uploads` preserva anexos
legados e contém regra que desativa execução de scripts.
