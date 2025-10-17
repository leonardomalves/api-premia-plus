@echo off
echo Iniciando 20 workers em paralelo...

start "Worker-1" php artisan queue:work --timeout=300 --sleep=1
start "Worker-2" php artisan queue:work --timeout=300 --sleep=1
start "Worker-3" php artisan queue:work --timeout=300 --sleep=1
start "Worker-4" php artisan queue:work --timeout=300 --sleep=1
st

echo 20 workers iniciados! Feche este terminal para parar todos.
pause