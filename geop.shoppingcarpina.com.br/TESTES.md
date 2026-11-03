# Testes

## Resultado nesta entrega

- Análise estática de 34 arquivos PHP e 36 tabelas: concluída.
- Busca de credenciais fixas, `COUNT+1`, limites de exportação, sessões públicas,
  rotas quebradas, PWA e WebView: concluída.
- PHP lint/runtime, MySQL, Composer e Java: não executados porque o Controle de
  Aplicativo do ambiente bloqueou os binários. Isso não é apresentado como aprovação.

## Roteiro obrigatório em homologação

1. Importar uma cópia anonimizada, executar migrations duas vezes e comparar contagens.
2. Testar login/logout, expiração, bloqueio, troca e recuperação de senha.
3. Validar cada perfil, inclusive acesso direto por URL e `GESTOR_SINDICO`.
4. Disparar 20 criações paralelas de OS e confirmar números únicos.
5. Enviar JPG/PNG/WebP válidos e arquivos falsos, PHP, excessivos e corrompidos.
6. Validar agente principal/auxiliar, conclusão, fotos obrigatórias e reabertura.
7. Rodar preventiva duas vezes no ciclo e simular sem persistência.
8. Testar todas as movimentações de estoque e aprovação de negativo.
9. Conferir relatório/exportação com mais de 200 registros e status `PAUSADA`.
10. Conferir chat com mais de 200 mensagens, paginação, leitura e usuário inativo.
11. Instalar/atualizar/remover a PWA e verificar que páginas privadas não entram no cache.
12. Compilar Android release, testar câmera, upload, downloads, SSL e links bloqueados.
13. Exercitar os 18 módulos listados em `RELATORIO_IMPLEMENTACOES.md`.
