# GEOP V7 — Notificações de liberações

## Novas funções

- Consulta automática de notificações autenticadas a cada 7 segundos.
- Alerta visual dentro do GEOP para novas solicitações e liberações aprovadas.
- Notificação do navegador com abertura da tela correta ao tocar no alerta.
- Padrões distintos de som e vibração para solicitação pendente e acesso aprovado.
- Botão de ativação de alertas no menu do sino.
- E-mail obrigatório no formulário público de solicitação.
- E-mail armazenado e pesquisável nas solicitações e liberações.
- Confirmação por e-mail no recebimento e na decisão, quando `mail()` estiver disponível.
- Notificação ao CFTV e Controle de Acesso após aprovação ou cadastro manual.

## Segurança

- A API de notificações exige autenticação.
- A consulta filtra exclusivamente pelo `usuario_id` da sessão.
- Apenas notificações não lidas e posteriores ao cursor são entregues ao navegador.
- Respostas da API usam `no-store` e `X-Content-Type-Options: nosniff`.
- O e-mail é validado com `FILTER_VALIDATE_EMAIL`.

## Compatibilidade

- Mantidos os menus modulares da V4.
- Mantidos os filtros CFTV de Câmeras OFF e Ocorrências.
- Mantidos Limpeza V3, OL V5 e migração OS → OL V6.3.
- Registros anteriores continuam aceitos com e-mail nulo.

## V7.1
- Removida a dependência de information_schema na atualização SQL.
- Adicionado instalador interno exclusivo do TI em public/atualizar_notificacoes_v7.php.
- Adicionado SQL alternativo compatível com cPanel/MariaDB.
