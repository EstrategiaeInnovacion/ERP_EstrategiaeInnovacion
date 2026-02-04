#!/bin/bash

echo "=== VERIFICACIÓN Y COPIA DE ARCHIVOS ESTÁTICOS ==="

# Crear directorios si no existen
mkdir -p public/css/Logistica
mkdir -p public/js/Logistica

echo "Verificando archivos CSS..."
if [ -f "public/css/Logistica/matriz-seguimiento.css" ]; then
    echo "✅ CSS encontrado: public/css/Logistica/matriz-seguimiento.css"
    ls -la public/css/Logistica/matriz-seguimiento.css
else
    echo "❌ CSS no encontrado: public/css/Logistica/matriz-seguimiento.css"
fi

echo ""
echo "Verificando archivos JavaScript..."
if [ -f "public/js/Logistica/matriz-seguimiento.js" ]; then
    echo "✅ JS encontrado: public/js/Logistica/matriz-seguimiento.js"
    ls -la public/js/Logistica/matriz-seguimiento.js
else
    echo "❌ JS no encontrado: public/js/Logistica/matriz-seguimiento.js"
fi

echo ""
echo "Verificando permisos de directorio public..."
ls -la public/

echo ""
echo "Verificando configuración del servidor web..."
echo "Asegúrate de que el servidor web esté sirviendo desde el directorio /public"

echo ""
echo "Si los archivos no existen, cópialos desde el repositorio local:"
echo "scp -r public/css/Logistica/ usuario@servidor:/var/www/Sistema_Tickets_E-I/public/css/"
echo "scp -r public/js/Logistica/ usuario@servidor:/var/www/Sistema_Tickets_E-I/public/js/"

echo ""
echo "Verifica también que el .htaccess esté configurado correctamente:"
cat public/.htaccess 2>/dev/null || echo "❌ Archivo .htaccess no encontrado"