# Windows equivalent of start.sh - launches everything ShopMate AI needs as
# plain local processes. No Docker. Ctrl+C stops all of them together.
#
# start.sh remains the Linux/LAMPP path and is still the documented one in
# README.md; this exists because the project also has to run on a Windows
# box where MySQL comes from XAMPP and PHP/Python are installed separately.
#
# Override any of these if your paths or ports differ:
#   $env:PHP_BIN, $env:MYSQLD_BIN, $env:LARAVEL_PORT, $env:AI_PORT

$ErrorActionPreference = 'Stop'

$RootDir      = $PSScriptRoot
$LaravelPort  = if ($env:LARAVEL_PORT) { $env:LARAVEL_PORT } else { '8010' }
$AiPort       = if ($env:AI_PORT)      { $env:AI_PORT }      else { '8001' }
$PhpBin       = if ($env:PHP_BIN)      { $env:PHP_BIN }      else { "$env:USERPROFILE\tools\php85\php.exe" }
$MysqldBin    = if ($env:MYSQLD_BIN)   { $env:MYSQLD_BIN }   else { 'C:\xampp\mysql\bin\mysqld.exe' }
$VenvPython   = Join-Path $RootDir 'ai-service\.venv\Scripts\python.exe'

foreach ($tool in @(@{p=$PhpBin; n='PHP'}, @{p=$VenvPython; n='AI service venv'})) {
    if (-not (Test-Path $tool.p)) {
        Write-Error "$($tool.n) not found at $($tool.p). See README.md 'Running on Windows'."
    }
}

$procs = @()

# MySQL: XAMPP ships MariaDB but no service registration by default, so we
# start mysqld directly rather than through the control panel.
Write-Host '==> Starting MySQL (XAMPP MariaDB)...'
if (Test-NetConnection -ComputerName 127.0.0.1 -Port 3306 -InformationLevel Quiet -WarningAction SilentlyContinue) {
    Write-Host '    already running.'
} else {
    $procs += Start-Process -FilePath $MysqldBin `
        -ArgumentList '--defaults-file=C:\xampp\mysql\bin\my.ini', '--standalone' `
        -PassThru -WindowStyle Hidden
    Start-Sleep -Seconds 4
}

Write-Host "==> Starting AI service on http://127.0.0.1:$AiPort ..."
$procs += Start-Process -FilePath $VenvPython `
    -ArgumentList '-m', 'uvicorn', 'app.main:app', '--host', '127.0.0.1', '--port', $AiPort `
    -WorkingDirectory (Join-Path $RootDir 'ai-service') -PassThru -NoNewWindow

Write-Host "==> Starting Laravel on http://127.0.0.1:$LaravelPort ..."
$procs += Start-Process -FilePath $PhpBin `
    -ArgumentList 'artisan', 'serve', '--host', '127.0.0.1', '--port', $LaravelPort `
    -WorkingDirectory (Join-Path $RootDir 'app') -PassThru -NoNewWindow

Write-Host ''
Write-Host 'ShopMate AI is running:'
Write-Host "  Chat UI:     http://127.0.0.1:$LaravelPort"
Write-Host "  AI service:  http://127.0.0.1:$AiPort/health"
Write-Host ''
Write-Host 'Press Ctrl+C to stop.'

try {
    while ($true) { Start-Sleep -Seconds 1 }
} finally {
    Write-Host ''
    Write-Host '==> Stopping...'
    foreach ($p in $procs) {
        if ($p -and -not $p.HasExited) { Stop-Process -Id $p.Id -Force -ErrorAction SilentlyContinue }
    }
}
