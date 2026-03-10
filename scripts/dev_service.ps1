# scripts/dev_service.ps1
# PowerShell wrapper to run dev_service.php as a background job on Windows

param(
    [string]$Action = 'start',
    [string]$ServerCmd = 'php -S localhost:8080 -t public'
)

$scriptPath = Join-Path $PSScriptRoot 'dev_service.php'

if ($Action -eq 'start') {
    Write-Host "Starting dev_service as background job..."
    $job = Start-Job -ScriptBlock { param($p) php $p } -ArgumentList $scriptPath
    Write-Host "Job started. Id=$($job.Id)"; Write-Host "Use 'Get-Job' and 'Stop-Job -Id <id>' to stop.";
}
elseif ($Action -eq 'stop') {
    Write-Host "Stopping dev_service background jobs..."
    Get-Job | Where-Object { $_.Command -like '*dev_service.php*' } | ForEach-Object { Stop-Job $_; Remove-Job $_; Write-Host "Stopped job Id=$($_.Id)" }
}
elseif ($Action -eq 'run') {
    Write-Host "Running dev_service in foreground..."
    php $scriptPath run $ServerCmd
}
else {
    Write-Host "Usage: .\dev_service.ps1 -Action start|stop|run [-ServerCmd 'php -S localhost:8080 -t public']"
}
