# ChatApp Setup Script for Windows
# Run this script in PowerShell

Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "   ChatApp Setup Script" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

# Check if running in correct directory
if (-not (Test-Path ".\ChatApp_backend_fresh") -or -not (Test-Path ".\frontend")) {
    Write-Host "Error: Please run this script from the ChatApp root directory!" -ForegroundColor Red
    exit 1
}

Write-Host "Step 1: Setting up Backend (Laravel)..." -ForegroundColor Yellow

# Backend setup
Set-Location .\ChatApp_backend_fresh

Write-Host "Installing Composer dependencies..."
herd composer install

if (-not (Test-Path ".env")) {
    Write-Host "Creating .env file..."
    Copy-Item .env.example .env
}

Write-Host "Generating application key..."
php artisan key:generate

Write-Host "Creating SQLite database..."
New-Item -ItemType File -Path database\database.sqlite -Force

Write-Host "Running migrations..."
php artisan migrate

Write-Host "Linking to Herd..."
herd link chatapp-api

Set-Location ..

Write-Host ""
Write-Host "Step 2: Setting up Frontend (Vue.js)..." -ForegroundColor Yellow

# Frontend setup
Set-Location .\frontend

Write-Host "Installing NPM dependencies..."
npm install

Set-Location ..

Write-Host ""
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "   Setup Complete!" -ForegroundColor Green
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "1. Backend is running at: http://chatapp-api.test"
Write-Host "2. Start frontend: cd frontend && npm run dev"
Write-Host "3. Open: http://localhost:3000"
