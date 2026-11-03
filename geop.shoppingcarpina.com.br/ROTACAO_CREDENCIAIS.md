# Rotação obrigatória

- Trocar a senha do usuário do banco que estava publicada no código e configurar as novas variáveis no servidor.
- Trocar todas as senhas administrativas, especialmente contas TI e contas que ainda usam senha inicial/provisória.
- Invalidar todas as sessões existentes; elas foram retiradas do pacote.
- Revogar e recriar tokens de convite, reabertura, edição CFTV e links públicos emitidos antes desta correção.
- Substituir a chave Android de depuração caso qualquer APK assinado com ela tenha sido distribuído.
- Gerar um `APP_KEY` aleatório e exclusivo para produção.

Valores secretos não são reproduzidos neste documento.
