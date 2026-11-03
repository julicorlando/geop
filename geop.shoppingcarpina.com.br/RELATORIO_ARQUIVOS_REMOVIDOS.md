# Arquivos retirados da distribuição

Os itens abaixo foram movidos, sem exclusão, para `../_quarantine_geop_20260715`. Nenhum upload legítimo foi removido.

| Item | Motivo | Evidência de que não pertence ao runtime web |
|---|---|---|
| `public/error_log` | Log interno publicamente exposto | Apache/PHP escreve logs fora do DocumentRoot; o código não o referencia |
| `tmp/sessions/` (3.276 arquivos) | Sessões reais incluídas na distribuição | A versão corrigida usa `var/sessions` ou `SESSION_PATH` |
| `sql/shoppingcarpinac_geopsc.sql` | Dump com dados reais | Usado somente como fonte para migração; não é lido pela aplicação |
| `android/build/`, `android/dist/`, `android/keystore/` | Builds, APK de depuração e chave | Não são dependências do servidor PHP |

Hashes de preservação: dump `53DD...4788`, log `62F7...8ACF`, keystore `57CE...097F` (SHA-256 completo registrado no inventário da quarentena).
