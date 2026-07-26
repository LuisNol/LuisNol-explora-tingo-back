<#
.SYNOPSIS
Despliega la aplicacion Laravel en produccion usando Docker.
Lee variables desde el .env del proyecto.
#>

param(
    [switch]$SkipMigrations,
    [switch]$SkipSeeders
)

$ErrorActionPreference = "Stop"

Write-Host "=== Despliegue de ExploreTingo Backend ===" -ForegroundColor Cyan

$appEnv = docker compose -f docker-compose.prod.yml run --rm --entrypoint "sh -c 'echo $APP_ENV'" app 2>$null
if ($LASTEXITCODE -ne 0) {
    $appEnv = "local"
}

if ($appEnv -ne "production") {
    Write-Host "Advertencia: APP_ENV no es 'production'. Editá tu .env" -ForegroundColor Yellow
}

Write-Host "1. Construyendo imagen de produccion..." -ForegroundColor Yellow
docker compose -f docker-compose.prod.yml build

Write-Host "2. Deteniendo servicios anteriores..." -ForegroundColor Yellow
docker compose -f docker-compose.prod.yml down

Write-Host "3. Iniciando servicios..." -ForegroundColor Yellow
docker compose -f docker-compose.prod.yml up -d

Write-Host "4. Ejecutando migraciones..." -ForegroundColor Yellow
if (-not $SkipMigrations) {
    docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
} else {
    Write-Host "Saltando migraciones..." -ForegroundColor Gray
}

Write-Host "5. Ejecutando seeders..." -ForegroundColor Yellow
if (-not $SkipSeeders) {
    docker compose -f docker-compose.prod.yml exec app php artisan db:seed --force
} else {
    Write-Host "Saltando seeders..." -ForegroundColor Gray
}

Write-Host "6. Optimizando aplicacion..." -ForegroundColor Yellow
docker compose -f docker-compose.prod.yml exec app php artisan optimize

Write-Host "=== Despliegue completado ===" -ForegroundColor Green
Write-Host "Accede a: http://localhost:8000" -ForegroundColor Cyan
