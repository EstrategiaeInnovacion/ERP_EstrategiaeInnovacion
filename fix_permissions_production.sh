#!/bin/bash

echo "=== SOLUCIONANDO PROBLEMA DE PERMISOS PARA ENVÍO DE CORREOS ==="

echo "1. Creando directorio temporal si no existe..."
mkdir -p /var/www/Sistema_Tickets_E-I/public/temp
mkdir -p /var/www/Sistema_Tickets_E-I/storage/temp
mkdir -p /var/www/Sistema_Tickets_E-I/storage/app/temp

echo "2. Ajustando permisos de directorios temporales..."
# Dar permisos de escritura al servidor web
chown -R www-data:www-data /var/www/Sistema_Tickets_E-I/public/temp
chown -R www-data:www-data /var/www/Sistema_Tickets_E-I/storage/temp
chown -R www-data:www-data /var/www/Sistema_Tickets_E-I/storage/app/temp

# Permisos 775 para directorios (lectura, escritura, ejecución)
chmod -R 775 /var/www/Sistema_Tickets_E-I/public/temp
chmod -R 775 /var/www/Sistema_Tickets_E-I/storage/temp  
chmod -R 775 /var/www/Sistema_Tickets_E-I/storage/app/temp

echo "3. Ajustando permisos generales de storage..."
chown -R www-data:www-data /var/www/Sistema_Tickets_E-I/storage
chmod -R 775 /var/www/Sistema_Tickets_E-I/storage

echo "4. Ajustando permisos de bootstrap/cache..."
chown -R www-data:www-data /var/www/Sistema_Tickets_E-I/bootstrap/cache
chmod -R 775 /var/www/Sistema_Tickets_E-I/bootstrap/cache

echo "5. Verificando permisos aplicados..."
echo "Directorio public/temp:"
ls -la /var/www/Sistema_Tickets_E-I/public/temp/

echo ""
echo "Directorio storage:"
ls -la /var/www/Sistema_Tickets_E-I/storage/

echo ""
echo "6. Test de escritura..."
echo "test" > /var/www/Sistema_Tickets_E-I/public/temp/test_write.txt 2>/dev/null
if [ -f "/var/www/Sistema_Tickets_E-I/public/temp/test_write.txt" ]; then
    echo "✅ Test de escritura exitoso"
    rm /var/www/Sistema_Tickets_E-I/public/temp/test_write.txt
else
    echo "❌ Test de escritura falló"
fi

echo ""
echo "7. Verificando usuario del servidor web..."
echo "Usuario actual: $(whoami)"
echo "Proceso Apache/Nginx debería ejecutarse como: www-data"

echo ""
echo "✅ PERMISOS CONFIGURADOS"
echo ""
echo "Si el problema persiste, verificar:"
echo "- SELinux (si está habilitado): setsebool -P httpd_can_network_connect 1"
echo "- Espacio en disco: df -h"
echo "- Logs del servidor web: tail -f /var/log/apache2/error.log"