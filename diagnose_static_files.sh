#!/bin/bash

echo "=== DIAGNÓSTICO COMPLETO DE ARCHIVOS ESTÁTICOS ==="

echo "1. Verificando estructura de directorios..."
echo "Directorio CSS:"
ls -la public/css/Logistica/ 2>/dev/null || echo "❌ Directorio public/css/Logistica/ no existe"

echo ""
echo "Directorio JS:"
ls -la public/js/Logistica/ 2>/dev/null || echo "❌ Directorio public/js/Logistica/ no existe"

echo ""
echo "2. Verificando archivos específicos..."
files=(
    "public/css/Logistica/matriz-seguimiento.css"
    "public/css/Logistica/export-styles.css" 
    "public/css/Logistica/catalogos.css"
    "public/js/Logistica/matriz-seguimiento.js"
    "public/js/Logistica/catalogos.js"
    "public/js/Logistica/correos-cc.js"
)

for file in "${files[@]}"; do
    if [ -f "$file" ]; then
        echo "✅ $file ($(stat -f%z "$file" 2>/dev/null || stat -c%s "$file" 2>/dev/null) bytes)"
    else
        echo "❌ $file - NO EXISTE"
    fi
done

echo ""
echo "3. Verificando permisos..."
chmod -R 644 public/css/Logistica/* 2>/dev/null
chmod -R 644 public/js/Logistica/* 2>/dev/null
echo "Permisos ajustados"

echo ""
echo "4. Verificando configuración del servidor web..."
echo "DocumentRoot debería apuntar al directorio /public"
echo "URL base: $(php -r "echo env('APP_URL');")"

echo ""
echo "5. Test de acceso HTTP (ejecutar manualmente):"
echo "curl -I https://soporteites.ddns.net/css/Logistica/matriz-seguimiento.css"
echo "curl -I https://soporteites.ddns.net/js/Logistica/matriz-seguimiento.js"

echo ""
echo "6. Limpieza de cache..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear

echo "✅ Diagnóstico completado"