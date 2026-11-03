# Relatório das 18 funcionalidades

As migrations criam modelos persistentes e índices para: patrimônio/QR (`ativos`);
checklists; fotos por fase; ciclos preventivos; tokens públicos; aprovações; rota e
disponibilidade; conhecimento; passagem de turno; terceiros; assinaturas; dashboard;
recorrências; mapa por coordenadas; relatório de reunião; IoT; e monitoramento de
CFTV/rede. A tela `public/modulos.php` expõe o catálogo operacional e `api_iot.php`
recebe eventos com Bearer token armazenado como hash.

Os módulos são incrementais e reutilizam OS, auditoria, anexos e RBAC existentes.
Assinaturas são aceites simples, explicitamente não certificados. Mapas e rotas não
dependem de API paga. Monitoramento usa falhas consecutivas configuráveis.

Antes de produção, conclua o roteiro de homologação de `TESTES.md`; o ambiente desta
entrega não permitiu executar PHP/MySQL/Android.
