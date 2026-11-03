# Integração de patrimônio externo

A integração é somente leitura no banco externo. O GEOP nunca atualiza ou exclui o cadastro original. Use um usuário MySQL restrito a `SELECT` e mantenha as variáveis no ambiente privado do PHP.

## Mapeamento necessário

O dump `assets.sql` fornecido em 15/07/2026 foi analisado. Ele usa MariaDB 10.11 e a tabela correta é `assets`, em `utf8mb4`. Mapeamento confirmado:

| Campo GEOP | Coluna externa |
|---|---|
| ID externo | `id` |
| Código de barras / número patrimonial | `tomb_number` |
| Nome/modelo | `model` |
| Descrição/observações | `notes` |
| Categoria (ID) | `item_id` |
| Marca | `brand` |
| Modelo | `model` |
| Número de série | `serial_number` |
| Responsável (ID) | `employee_id` |
| Setor (ID) | `department_id` |
| Data de aquisição | `nfe_date` |
| Situação | `asset_status` |

O dump isolado não inclui as tabelas relacionadas de itens, funcionários e departamentos. Por isso, esses três campos são exibidos como IDs até que os respectivos esquemas sejam fornecidos. Não há coluna de localização ou garantia em `assets`.

Antes de habilitar, confirme com o responsável pelo patrimônio:

- nome exato da tabela ou view liberada para leitura;
- coluna de ID externo;
- coluna de código de barras;
- opcionalmente número, nome, descrição, categoria, marca, modelo, série, setor, localização, responsável, situação, aquisição, garantia e observações.

O `.env.example` já contém esse mapeamento. Preencha host, banco, usuário e senha apenas no ambiente privado do servidor. Identificadores aceitam somente letras, números e sublinhado. Defina `PATRIMONIO_DB_ENABLED=true` somente depois do teste em homologação.

O usuário externo deve possuir somente `SELECT`. A coluna `tomb_number` possui índice no dump fornecido, adequado à consulta exata.

O timeout de conexão é curto e falhas retornam mensagem genérica. A abertura da OS continua mesmo quando o banco externo está indisponível. Códigos permanecem strings, preservando zeros à esquerda.

## Leitura

Leitores USB funcionam como teclado. Em navegadores compatíveis, `BarcodeDetector` usa a câmera local para Code 128, Code 39, EAN-13, EAN-8 e QR Code. Nenhuma imagem é enviada ou armazenada. Quando a API não existe ou a permissão é negada, use a entrada manual.
