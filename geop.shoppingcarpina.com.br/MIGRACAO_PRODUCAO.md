# Migração de produção

1. Coloque o sistema em manutenção e gere backups consistentes do banco e dos uploads.
2. Valide os backups restaurando-os em ambiente isolado.
3. Configure as variáveis listadas no `.env.example`; não reutilize a credencial exposta.
4. Execute `sql/migrations/001_security_foundation.sql` primeiro na cópia e depois na produção.
5. Aponte o DocumentRoot para `public/`, configure HTTPS e uma pasta privada para sessões.
6. Valide login, troca de senha, logout, perfis e criação/consulta de OS antes de liberar tráfego.

Rollback: restaure o backup completo. O rollback parcial comentado na migração só é seguro se as tabelas novas estiverem vazias. Ações manuais: rotação de credenciais/tokens, troca das senhas administrativas e invalidação de sessões.
