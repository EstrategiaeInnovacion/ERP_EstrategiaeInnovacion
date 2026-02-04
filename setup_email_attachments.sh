#!/bin/bash

echo "=== CONFIGURACIÓN COMPLETA PARA ENVÍO DE CORREOS ==="

echo "1. Creando directorios temporales..."
mkdir -p /var/www/Sistema_Tickets_E-I/public/temp
mkdir -p /var/www/Sistema_Tickets_E-I/storage/app/public/temp

echo "2. Configurando enlace simbólico de storage (si no existe)..."
cd /var/www/Sistema_Tickets_E-I
if [ ! -L "public/storage" ]; then
    php artisan storage:link
    echo "✅ Enlace simbólico de storage creado"
else
    echo "✅ Enlace simbólico de storage ya existe"
fi

echo "3. Ajustando permisos..."
# Permisos para directorios temporales
chown -R www-data:www-data public/temp storage/app/public/temp
chmod -R 775 public/temp storage/app/public/temp

# Permisos generales de storage
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Permisos para el enlace simbólico
chown -h www-data:www-data public/storage 2>/dev/null || true

echo "4. Verificando configuración..."
echo "Directorio public/temp:"
ls -la public/temp/ 2>/dev/null || echo "Directorio vacío"

echo ""
echo "Directorio storage/app/public/temp:"
ls -la storage/app/public/temp/ 2>/dev/null || echo "Directorio vacío"

echo ""
echo "Enlace simbólico public/storage:"
ls -la public/storage 2>/dev/null || echo "No existe"

echo ""
echo "5. Test de escritura..."
# Test en public/temp
echo "test" > public/temp/test_write.txt 2>/dev/null
if [ -f "public/temp/test_write.txt" ]; then
    echo "✅ Escritura en public/temp: OK"
    rm public/temp/test_write.txt
else
    echo "❌ Escritura en public/temp: FALLA"
fi

# Test en storage/app/public/temp
echo "test" > storage/app/public/temp/test_write.txt 2>/dev/null
if [ -f "storage/app/public/temp/test_write.txt" ]; then
    echo "✅ Escritura en storage/app/public/temp: OK"
    rm storage/app/public/temp/test_write.txt
else
    echo "❌ Escritura en storage/app/public/temp: FALLA"
fi

echo ""
echo "6. Verificando espacio en disco..."
df -h /var/www/

echo ""
echo "✅ CONFIGURACIÓN COMPLETADA"
echo ""
echo "Para verificar logs en tiempo real:"
echo "tail -f /var/log/apache2/error.log"
echo "tail -f storage/logs/laravel.log"