# Start 20 Laravel Queue Workers in parallel
Write-Host "Iniciando 20 workers em paralelo..." -ForegroundColor Green

$jobs = @()
for ($i = 1; $i -le 20; $i++) {
    $job = Start-Job -ScriptBlock {
        param($workerNum)
        Set-Location "D:\Projects\Neutrino\api-premia-plus"
        php artisan queue:work --timeout=300 --sleep=1 --name="worker-$workerNum"
    } -ArgumentList $i -Name "Worker-$i"
    
    $jobs += $job
    Write-Host "Worker $i iniciado" -ForegroundColor Cyan
}

Write-Host "`n20 workers rodando! Pressione Ctrl+C para parar todos.`n" -ForegroundColor Yellow

# Monitor workers
try {
    while ($true) {
        $running = ($jobs | Where-Object { $_.State -eq "Running" }).Count
        Write-Host "Workers ativos: $running/20" -ForegroundColor Green
        Start-Sleep -Seconds 10
    }
}
catch {
    Write-Host "`nParando todos os workers..." -ForegroundColor Red
    $jobs | Stop-Job
    $jobs | Remove-Job
    Write-Host "Todos os workers foram parados." -ForegroundColor Red
}