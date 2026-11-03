$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$failures = [Collections.Generic.List[string]]::new()
function Reject([string]$Pattern,[string]$Description) {
    $matches = rg -n $Pattern $root -g '*.php' -g '*.js' -g '*.java' -g '!public/error_log' 2>$null
    if ($LASTEXITCODE -eq 0) { $failures.Add("$Description`n$matches") }
}
Reject "DB_PASS\s*=\s*getenv\([^)]+\)\s*\?:" 'Senha de banco com fallback'
Reject "ti@shoppingcarpina\.com\.br.*123456" 'Login legado'
Reject "MIXED_CONTENT_COMPATIBILITY_MODE" 'Conteúdo misto no Android'
Reject "allowBackup=.true." 'Backup Android habilitado'
Reject "LIMIT 100" 'Limite artificial de relatório/convite'
if ($failures.Count) { $failures | ForEach-Object { Write-Error $_ }; exit 1 }
Write-Output 'Static security checks: OK'
