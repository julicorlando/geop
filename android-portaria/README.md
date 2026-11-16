# GEOP Portaria (Android · Sunmi P2 / P2-B)

App da portaria: abre a tela **Portaria** do GEOP (`/cftv/controle/`), com as liberações internas e as
solicitações aprovadas do dia, e registra entrada/saída pela credencial (QR).

- **Leitor embutido Sunmi:** o código chega por broadcast (`com.sunmi.scanner.ACTION_DATA_CODE_RECEIVED`) e é
  registrado na hora, com bipe e vibração. No modo "teclado" do leitor também funciona (o campo já vem focado).
- **Sem leitor:** botão "Usar câmera" da página (o app libera a câmera só para o servidor do GEOP).
- **Servidor:** padrão `https://tst.shoppingcarpina.com.br`; troque no ⚙ (ex.: `https://geop.shoppingcarpina.com.br`).
- **Login:** use um usuário com o perfil **Controle de acesso** (permissão "Registrar entrada/saída").
  A sessão fica salva no aparelho.

## Gerar o APK

O GitHub Actions gera o APK a cada push nesta pasta (workflow *APK Portaria (Sunmi)*), em *Artifacts*.
Para manter a mesma assinatura entre versões (atualizar sem desinstalar), cadastre os segredos
`PORTARIA_KEYSTORE_B64` (keystore em base64) e `PORTARIA_KEYSTORE_PASSWORD`; sem eles o APK sai com a
chave de debug do build.

Localmente: `cd android-portaria && gradle assembleRelease` (Android SDK 34 e JDK 17).
