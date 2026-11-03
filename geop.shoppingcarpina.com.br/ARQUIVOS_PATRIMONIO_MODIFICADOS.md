# Arquivos da integração de patrimônio

## Novos

- `config/patrimonio.php`: configuração e segunda conexão PDO.
- `includes/patrimonio.php`: normalização, consulta preparada e snapshots.
- `includes/ordem_patrimonios_widget.php`: patrimônios da OS, comparação, atualização e desvinculação.
- `public/patrimonios.php`: consulta manual, USB e câmera.
- `public/patrimonio_api.php`: API autenticada, autorizada, auditada e sem cache.
- `public/assets/patrimonio-scanner.js`: scanner local e paginação.
- `sql/migrations/003_external_assets.sql`: vínculos, índices, rate limit e permissões.
- `PATRIMONIO_EXTERNO.md`, `RESULTADO_TESTES_PATRIMONIO.md` e `tests/patrimonio_unit.php`.

## Atualizados

- `.env.example`, `README.md`, `MATRIZ_PERMISSOES.md` e `RELATORIO_CORRECOES.md`.
- `includes/layout.php`: item de navegação autorizado.
- `public/ordem_form.php`: leitura e vínculo opcional na abertura.
- `public/ordem_detalhe.php`: widget de vínculos e dados atuais.
- `includes/operacional.php`, `includes/usuario_convites.php` e cópias operacionais legadas: canais externos desabilitados.
