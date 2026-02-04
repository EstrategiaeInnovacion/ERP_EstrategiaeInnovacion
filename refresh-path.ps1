# Script para refrescar PATH y usar PHP de Laragon
$env:PATH = [System.Environment]::GetEnvironmentVariable("PATH","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("PATH","User")

# Verificar que PHP funciona
php --version

Write-Host "✅ PATH actualizado. Ahora puedes usar 'php artisan' directamente." -ForegroundColor Green
Write-Host "💡 Ejecuta este script cada vez que abras una nueva terminal:" -ForegroundColor Yellow
Write-Host "   .\refresh-path.ps1" -ForegroundColor Cyan