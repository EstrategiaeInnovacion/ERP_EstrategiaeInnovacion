const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

(async () => {
    console.log('Iniciando captura de pantallas...');
    const browser = await puppeteer.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });
    const page = await browser.newPage();
    await page.setViewport({ width: 1280, height: 800 });

    const outputDir = path.join(__dirname, '../docs/user/images');
    if (!fs.existsSync(outputDir)) {
        fs.mkdirSync(outputDir, { recursive: true });
    }

    try {
        // 1. Pantalla de Login
        console.log('Capturando pantalla de Login...');
        await page.goto('http://localhost:8000/login', { waitUntil: 'networkidle2' });
        await page.screenshot({ path: path.join(outputDir, '01-login.png') });
        console.log('✅ 01-login.png guardado.');

        // Iniciar Sesión
        console.log('Iniciando sesión...');
        await page.type('input[type="email"]', 'admin@estrategiaeinnovacion.com.mx');
        await page.type('input[type="password"]', 'password');
        
        // Hacer clic en Iniciar Sesión
        await Promise.all([
            page.click('button[type="submit"]'),
            page.waitForNavigation({ waitUntil: 'networkidle2' })
        ]);
        console.log('Sesión iniciada con éxito.');

        // 2. Portal de Inicio
        console.log('Capturando Portal Corporativo...');
        await page.goto('http://localhost:8000/', { waitUntil: 'networkidle2' });
        await page.screenshot({ path: path.join(outputDir, '02-portal-inicio.png') });
        console.log('✅ 02-portal-inicio.png guardado.');

        // 3. Dashboard Recursos Humanos
        console.log('Capturando Dashboard RH...');
        await page.goto('http://localhost:8000/recursos-humanos', { waitUntil: 'networkidle2' });
        await page.screenshot({ path: path.join(outputDir, '03-dashboard-rh.png') });
        console.log('✅ 03-dashboard-rh.png guardado.');

        // 4. Expedientes
        console.log('Capturando Lista de Expedientes...');
        await page.goto('http://localhost:8000/recursos-humanos/expedientes', { waitUntil: 'networkidle2' });
        await page.screenshot({ path: path.join(outputDir, '04-expedientes.png') });
        console.log('✅ 04-expedientes.png guardado.');

        // 5. Reloj Checador
        console.log('Capturando Reloj Checador...');
        await page.goto('http://localhost:8000/recursos-humanos/reloj', { waitUntil: 'networkidle2' });
        await page.screenshot({ path: path.join(outputDir, '05-reloj-checador.png') });
        console.log('✅ 05-reloj-checador.png guardado.');

        // 6. Digitalización
        console.log('Capturando Módulo de Digitalización...');
        await page.goto('http://localhost:8000/digitalizacion', { waitUntil: 'networkidle2' });
        await page.screenshot({ path: path.join(outputDir, '06-digitalizacion.png') });
        console.log('✅ 06-digitalizacion.png guardado.');

        // 7. Tickets de Soporte
        console.log('Capturando Módulo de Tickets...');
        await page.goto('http://localhost:8000/ticket/mis-tickets', { waitUntil: 'networkidle2' });
        await page.screenshot({ path: path.join(outputDir, '07-tickets.png') });
        console.log('✅ 07-tickets.png guardado.');

        // 8. Tablero de Actividades
        console.log('Capturando Tablero de Actividades...');
        await page.goto('http://localhost:8000/activities', { waitUntil: 'networkidle2' });
        await page.screenshot({ path: path.join(outputDir, '08-actividades-tablero.png') });
        console.log('✅ 08-actividades-tablero.png guardado.');

        // 9. Dashboard Logística
        console.log('Capturando Dashboard Logística...');
        await page.goto('http://localhost:8000/logistica', { waitUntil: 'networkidle2' });
        await page.screenshot({ path: path.join(outputDir, '09-logistica.png') });
        console.log('✅ 09-logistica.png guardado.');

        // 10. Matriz de Seguimiento
        console.log('Capturando Matriz de Seguimiento...');
        await page.goto('http://localhost:8000/logistica/matriz-seguimiento', { waitUntil: 'networkidle2' });
        await page.screenshot({ path: path.join(outputDir, '10-matriz-seguimiento.png') });
        console.log('✅ 10-matriz-seguimiento.png guardado.');

        // 11. Dashboard Legal
        console.log('Capturando Dashboard Legal...');
        await page.goto('http://localhost:8000/legal', { waitUntil: 'networkidle2' });
        await page.screenshot({ path: path.join(outputDir, '11-legal.png') });
        console.log('✅ 11-legal.png guardado.');

        // 12. Dashboard Administración
        console.log('Capturando Dashboard Administración...');
        await page.goto('http://localhost:8000/administracion', { waitUntil: 'networkidle2' });
        await page.screenshot({ path: path.join(outputDir, '12-administracion.png') });
        console.log('✅ 12-administracion.png guardado.');

        // 13. Anexo 24
        console.log('Capturando Módulo Anexo 24...');
        await page.goto('http://localhost:8000/anexo24', { waitUntil: 'networkidle2' });
        await page.screenshot({ path: path.join(outputDir, '13-anexo24.png') });
        console.log('✅ 13-anexo24.png guardado.');

        // 14. Post-Operaciones
        console.log('Capturando Módulo Post-Operaciones...');
        await page.goto('http://localhost:8000/postoperaciones', { waitUntil: 'networkidle2' });
        await page.screenshot({ path: path.join(outputDir, '14-postoperaciones.png') });
        console.log('✅ 14-postoperaciones.png guardado.');

        // 15. Auditoría
        console.log('Capturando Módulo Auditoría...');
        await page.goto('http://localhost:8000/auditoria', { waitUntil: 'networkidle2' });
        await page.screenshot({ path: path.join(outputDir, '15-auditoria.png') });
        console.log('✅ 15-auditoria.png guardado.');

    } catch (error) {
        console.error('Error durante la captura:', error);
    } finally {
        await browser.close();
        console.log('Captura de pantalla finalizada.');
    }
})();
