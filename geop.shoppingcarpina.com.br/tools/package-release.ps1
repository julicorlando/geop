param([string]$Version = '2026.07.24')
$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$output = Join-Path $root "GEOP-$Version-producao.zip"
if (Test-Path -LiteralPath $output) { Remove-Item -LiteralPath $output -Force }
$staging = Join-Path ([IO.Path]::GetTempPath()) ("geop-release-" + [guid]::NewGuid())
New-Item -ItemType Directory -Path $staging | Out-Null
try {
    $excluded = @(
        '\\tmp\\','\\var\\','\\android\\build\\','\\android\\dist\\',
        '\\android\\keystore\\','\\tools\\','\\geop\\','\\public\\error_log$',
        '\\shoppingcarpinac_geopsc\.sql$','\\\.env$','\.apk$','\.aab$','\.keystore$','\.jks$'
    )
    Get-ChildItem -LiteralPath $root -Recurse -File | Where-Object {
        $path = $_.FullName
        $relativeCheck = $_.FullName.Substring($root.Length).TrimStart('\') -replace '\\','/'
        $topPrivate = $relativeCheck -match '^(tmp|var|sql|tools)/'
        -not $topPrivate -and -not ($excluded | Where-Object { $path -match $_ })
    } | ForEach-Object {
        $relative = $_.FullName.Substring($root.Length).TrimStart('\')
        $target = Join-Path $staging $relative
        New-Item -ItemType Directory -Force -Path (Split-Path -Parent $target) | Out-Null
        Copy-Item -LiteralPath $_.FullName -Destination $target
    }
    Compress-Archive -Path (Join-Path $staging '*') -DestinationPath $output -CompressionLevel Optimal
    Write-Output $output
} finally {
    if (Test-Path -LiteralPath $staging) {
        $resolved = (Resolve-Path -LiteralPath $staging).Path
        if ($resolved.StartsWith([IO.Path]::GetTempPath(), [StringComparison]::OrdinalIgnoreCase)) {
            Remove-Item -LiteralPath $resolved -Recurse -Force
        }
    }
}
