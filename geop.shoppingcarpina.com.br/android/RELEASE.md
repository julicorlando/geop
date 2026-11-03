# Build Android release

O pacote não distribui keystore. Gere uma chave fora do repositório:

```text
keytool -genkeypair -keystore /local-seguro/geop-release.jks -alias geop -keyalg RSA -keysize 4096 -validity 10000
```

Compile o APK/AAB com target SDK atual, alinhe com `zipalign`, assine com `apksigner`
e verifique com `apksigner verify --verbose`. Passe caminhos e senhas por variáveis
de ambiente/secret store; nunca grave senhas no script. O release deve usar
`allowBackup=false`, HTTPS, domínio permitido e `network_security_config.xml`.

Teste câmera/seletor, uploads, downloads via `DownloadManager`, retorno de Activity,
certificado inválido e navegação para hosts/esquemas não autorizados.
