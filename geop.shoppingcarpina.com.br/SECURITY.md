# Segurança

- Nunca publique `.env`, `var/`, `tmp/`, `sql/`, logs, APKs ou keystores.
- Troque imediatamente a senha de banco que existia no pacote original.
- Use HTTPS, usuário MySQL sem privilégios administrativos e backup criptografado.
- O armazenamento privado deve ficar fora do DocumentRoot. Uploads legados continuam
  em `public/uploads` por compatibilidade, protegidos contra execução.
- Tokens novos devem ser guardados como SHA-256 para busca e, quando necessário,
  `password_hash` para validação. Senhas usam `password_hash`/`password_verify`.
- `APP_DEBUG=false` em produção. Erros públicos exibem apenas `X-Request-Id`.
- Revise trimestralmente a matriz RBAC e os eventos de auditoria.

Não inclua dumps de produção no pacote distribuído.
