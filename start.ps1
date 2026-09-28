Write-Host "=== ReturnTrack 1-Klik Starter ===" -ForegroundColor Cyan

$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot

# Cek Docker
try { docker --version | Out-Null } catch {
    Write-Host "Docker tidak ditemukan. Install Docker Desktop dulu." -ForegroundColor Red
    pause; exit 1
}

Write-Host "[1/4] Build & up container..." -ForegroundColor Yellow
docker compose up --build -d
if ($LASTEXITCODE -ne 0) { Write-Host "Gagal docker compose up" -ForegroundColor Red; pause; exit 1 }

Write-Host "[2/4] Tunggu 5 detik..." -ForegroundColor Yellow
Start-Sleep -Seconds 5

Write-Host "[3/4] Migrate database..." -ForegroundColor Yellow
docker compose exec app php artisan migrate --force
if ($LASTEXITCODE -ne 0) {
    Write-Host "Migrate gagal, coba fresh..." -ForegroundColor Yellow
    docker compose exec app php artisan migrate:fresh --force
}

Write-Host "[4/4] Seed (jika belum ada)..." -ForegroundColor Yellow
docker compose exec app php artisan tinker --execute="if(\App\Models\User::count()==0){ (new Database\Seeders\DatabaseSeeder)->run(); echo 'seeded'; } else { echo 'already seeded: '. \App\Models\User::count() . ' users'; }" 2>$null
# Fallback: coba db:seed tapi ignore duplicate error
docker compose exec app php artisan db:seed --force 2>$null | Out-Null

Write-Host ""
Write-Host "=== SELESAI ===" -ForegroundColor Green
Write-Host "Buka: http://localhost:8000/login" -ForegroundColor Cyan
Write-Host "Login: admin@emza.test / password" -ForegroundColor White
Write-Host "       test@example.com / password" -ForegroundColor White
Write-Host ""
Write-Host "Import: /imports (drag 2 file A+B sekaligus, auto-detect)" -ForegroundColor Gray
Write-Host "Scan kabel: /scan (colok QR USB, scan JY/JX)" -ForegroundColor Gray
Write-Host ""
Write-Host "Stop: docker compose down" -ForegroundColor DarkGray
# Buka browser otomatis
try { Start-Process "http://localhost:8000/login" } catch {}

pause
