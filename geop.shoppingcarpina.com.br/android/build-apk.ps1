$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSScriptRoot
$androidRoot = Join-Path $projectRoot 'android'
$appRoot = Join-Path $androidRoot 'app'
$buildRoot = Join-Path $androidRoot 'build'
$distRoot = Join-Path $androidRoot 'dist'

$sdkRoot = if ($env:ANDROID_HOME) { $env:ANDROID_HOME } else { Join-Path $env:LOCALAPPDATA 'Android\Sdk' }
$platform = Get-ChildItem (Join-Path $sdkRoot 'platforms') -Directory |
    Sort-Object Name -Descending |
    Select-Object -First 1
$buildTools = Get-ChildItem (Join-Path $sdkRoot 'build-tools') -Directory |
    Where-Object { Test-Path (Join-Path $_.FullName 'aapt2.exe') } |
    Sort-Object Name -Descending |
    Select-Object -First 1

if (-not $platform) { throw 'Android platform nao encontrado no SDK.' }
if (-not $buildTools) { throw 'Android build-tools nao encontrado no SDK.' }

$javaHome = 'C:\Program Files\Android\Android Studio\jbr'
$javac = Join-Path $javaHome 'bin\javac.exe'
$jar = Join-Path $javaHome 'bin\jar.exe'
$keytool = Join-Path $javaHome 'bin\keytool.exe'

if (Test-Path $javaHome) {
    $env:JAVA_HOME = $javaHome
    $env:PATH = (Join-Path $javaHome 'bin') + ';' + $env:PATH
}

if (-not (Test-Path $javac)) { $javac = 'javac' }
if (-not (Test-Path $jar)) { $jar = 'jar' }
if (-not (Test-Path $keytool)) { $keytool = 'keytool' }

function Assert-LastExitCode([string] $step) {
    if ($LASTEXITCODE -ne 0) {
        throw "$step falhou com codigo $LASTEXITCODE."
    }
}

$aapt2 = Join-Path $buildTools.FullName 'aapt2.exe'
$d8 = Join-Path $buildTools.FullName 'd8.bat'
$zipalign = Join-Path $buildTools.FullName 'zipalign.exe'
$apksigner = Join-Path $buildTools.FullName 'apksigner.bat'
$androidJar = Join-Path $platform.FullName 'android.jar'

if (Test-Path $buildRoot) { Remove-Item -LiteralPath $buildRoot -Recurse -Force }
New-Item -ItemType Directory -Force -Path $buildRoot, $distRoot | Out-Null

$compiledRes = Join-Path $buildRoot 'compiled-res.zip'
$generatedRoot = Join-Path $buildRoot 'generated'
$classesRoot = Join-Path $buildRoot 'classes'
$dexRoot = Join-Path $buildRoot 'dex'
$androidJarLocal = Join-Path $buildRoot 'android.jar'
$classesJar = Join-Path $buildRoot 'classes.jar'
$unsignedApk = Join-Path $buildRoot 'geop-sc-pdu-unsigned.apk'
$alignedApk = Join-Path $buildRoot 'geop-sc-pdu-aligned.apk'
$finalApk = Join-Path $distRoot 'GEOP-SC-PDU-debug.apk'

New-Item -ItemType Directory -Force -Path $generatedRoot, $classesRoot, $dexRoot | Out-Null
Copy-Item -LiteralPath $androidJar -Destination $androidJarLocal -Force
Set-ItemProperty -LiteralPath $androidJarLocal -Name IsReadOnly -Value $false

& $aapt2 compile --dir (Join-Path $appRoot 'src\main\res') -o $compiledRes
Assert-LastExitCode 'aapt2 compile'
& $aapt2 link `
    -o $unsignedApk `
    -I $androidJarLocal `
    --manifest (Join-Path $appRoot 'src\main\AndroidManifest.xml') `
    --java $generatedRoot `
    --min-sdk-version 23 `
    --target-sdk-version 35 `
    --auto-add-overlay `
    $compiledRes
Assert-LastExitCode 'aapt2 link'

$sourceFiles = @(
    (Join-Path $appRoot 'src\main\java\br\com\shoppingcarpina\geopscpdu\MainActivity.java'),
    (Join-Path $generatedRoot 'br\com\shoppingcarpina\geopscpdu\R.java')
)

& $javac '-J-Dsun.zip.disableMemoryMapping=true' -encoding UTF-8 -source 8 -target 8 -classpath $androidJarLocal -d $classesRoot $sourceFiles
Assert-LastExitCode 'javac'
& $jar cf $classesJar -C $classesRoot .
Assert-LastExitCode 'jar classes'
& $d8 --min-api 23 --lib $androidJarLocal --output $dexRoot $classesJar
Assert-LastExitCode 'd8'
& $jar uf $unsignedApk -C $dexRoot classes.dex
Assert-LastExitCode 'jar'

$androidUserDir = Join-Path $androidRoot 'keystore'
$keystore = Join-Path $androidUserDir 'debug.keystore'
if (-not (Test-Path $keystore)) {
    New-Item -ItemType Directory -Force -Path $androidUserDir | Out-Null
    & $keytool -genkeypair -v `
        -keystore $keystore `
        -storepass android `
        -alias androiddebugkey `
        -keypass android `
        -keyalg RSA `
        -keysize 2048 `
        -validity 10000 `
        -dname 'CN=Android Debug,O=Android,C=US'
    Assert-LastExitCode 'keytool'
}

& $zipalign -f -p 4 $unsignedApk $alignedApk
Assert-LastExitCode 'zipalign'
& $apksigner sign `
    --ks $keystore `
    --ks-key-alias androiddebugkey `
    --ks-pass pass:android `
    --key-pass pass:android `
    --out $finalApk `
    $alignedApk
Assert-LastExitCode 'apksigner sign'
& $apksigner verify --verbose $finalApk
Assert-LastExitCode 'apksigner verify'

Write-Host "APK gerado em: $finalApk"
