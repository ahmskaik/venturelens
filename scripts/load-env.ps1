# Parse .env into process environment (simple KEY=VALUE, no export keyword)
param(
    [string]$Path = (Join-Path (Split-Path $PSScriptRoot -Parent) ".env")
)

if (-not (Test-Path $Path)) {
    Write-Error ".env not found at $Path"
    exit 1
}

Get-Content $Path | ForEach-Object {
    $line = $_.Trim()
    if ($line -eq '' -or $line.StartsWith('#')) { return }
    $idx = $line.IndexOf('=')
    if ($idx -lt 1) { return }
    $key = $line.Substring(0, $idx).Trim()
    $val = $line.Substring($idx + 1).Trim().Trim('"')
    Set-Item -Path "env:$key" -Value $val
}
