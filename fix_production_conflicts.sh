#!/bin/bash

# Script para resolver conflictos de clases en producción

echo "=== ELIMINANDO ARCHIVOS BACKUP CONFLICTIVOS ==="

# Eliminar archivos backup que causan conflictos de autoloader
rm -f app/Http/Controllers/Logistica/OperacionLogisticaController_backup.php
rm -f app/Http/Controllers/Logistica/OperacionLogisticaController.php.backup
rm -f app/Http/Controllers/Logistica/OperacionLogisticaController.php.backup2

echo "Archivos backup eliminados"

echo "=== REGENERANDO AUTOLOADER ==="
composer dump-autoload --optimize

echo "=== LIMPIANDO CACHES ==="
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo "=== CREANDO CACHES OPTIMIZADOS ==="
php artisan config:cache
php artisan route:cache

echo "=== VERIFICANDO RUTA ESPECÍFICA ==="
php artisan route:list --name="logistica.matriz-seguimiento"

echo ""
echo "✅ PROCESO COMPLETADO"
echo "La aplicación debería funcionar correctamente ahora"