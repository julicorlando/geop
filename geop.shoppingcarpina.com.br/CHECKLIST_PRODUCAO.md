# Checklist de produção

- [ ] Backup restaurado e validado
- [ ] DocumentRoot definido como `public/`
- [ ] HTTPS ativo; HSTS habilitado somente após validar todos os subdomínios
- [ ] Segredos fornecidos pelo ambiente e credencial antiga rotacionada
- [ ] `APP_URL` e `APP_KEY` definidos
- [ ] Migração 001 aplicada e registrada
- [ ] Sessões fora da área pública e permissão restritiva
- [ ] Senhas TI/administrativas trocadas
- [ ] Tokens antigos revogados
- [ ] Login, logout, troca de senha e RBAC validados
- [ ] Logs do servidor sem erros novos
- [ ] Backup automatizado do banco e uploads configurado
