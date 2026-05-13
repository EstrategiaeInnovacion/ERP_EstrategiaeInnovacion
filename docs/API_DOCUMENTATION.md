# API Documentation - ERP Estrategia e Innovación

## Información General

**URL Base:** `https://tu-dominio.com/api/v1`

**Versión:** 1.0.0

**Última actualización:** Febrero 2026

---

## Autenticación

La API utiliza **Laravel Sanctum** para la autenticación mediante tokens Bearer.

### Cómo autenticarse

1. Obtén un token usando el endpoint `/api/v1/auth/login`
2. Incluye el token en todas las peticiones protegidas:
   ```
   Authorization: Bearer {tu_token}
   ```

### Headers requeridos

```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token}  // Solo para endpoints protegidos
```

---

## Endpoints

### 🔓 Endpoints Públicos

#### POST /api/v1/auth/login

Iniciar sesión y obtener un token de acceso.

**Request:**
```json
{
    "email": "usuario@ejemplo.com",
    "password": "tu_contraseña",
    "device_name": "mi-aplicacion"  // Opcional, default: "api-client"
}
```

**Response exitoso (200):**
```json
{
    "success": true,
    "message": "Inicio de sesión exitoso",
    "data": {
        "user": {
            "id": 1,
            "name": "Usuario Ejemplo",
            "email": "usuario@ejemplo.com",
            "role": "user",
            "status": "approved",
            "area": "logistica"
        },
        "token": "1|abc123def456ghj789...",
        "token_type": "Bearer",
        "expires_at": null
    }
}
```

**Errores posibles:**

| Código | Descripción |
|--------|-------------|
| 401 | Credenciales incorrectas |
| 403 | Cuenta bloqueada, pendiente o rechazada |
| 422 | Error de validación |

**Ejemplo de error (401):**
```json
{
    "success": false,
    "message": "Credenciales incorrectas",
    "errors": {
        "password": ["La contraseña es incorrecta"]
    }
}
```

---

#### POST /api/v1/auth/validate-token

Validar si un token es válido y está activo.

**Request:**
```json
{
    "token": "1|abc123def456ghj789..."
}
```

**Response (token válido):**
```json
{
    "success": true,
    "valid": true,
    "data": {
        "user": {
            "id": 1,
            "name": "Usuario Ejemplo",
            "email": "usuario@ejemplo.com",
            "role": "user",
            "status": "approved"
        }
    }
}
```

**Response (token inválido):**
```json
{
    "success": true,
    "valid": false,
    "message": "Token inválido o expirado"
}
```

---

### 🔐 Endpoints Protegidos

> Requieren header `Authorization: Bearer {token}`

#### GET /api/v1/auth/me

Obtener información del usuario autenticado.

**Response (200):**
```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "name": "Usuario Ejemplo",
            "email": "usuario@ejemplo.com",
            "role": "user",
            "status": "approved",
            "area": "logistica",
            "empleado": {
                "id": 15,
                "nombre_completo": "Juan Pérez García",
                "puesto": "Coordinador",
                "departamento": "Logística"
            }
        }
    }
}
```

---

#### POST /api/v1/auth/logout

Cerrar sesión y revocar el token actual.

**Response (200):**
```json
{
    "success": true,
    "message": "Sesión cerrada exitosamente"
}
```

---

#### POST /api/v1/auth/refresh

Renovar el token actual (revoca el actual y genera uno nuevo).

**Response (200):**
```json
{
    "success": true,
    "message": "Token renovado exitosamente",
    "data": {
        "token": "2|xyz789abc456...",
        "token_type": "Bearer"
    }
}
```

---

## Códigos de Estado HTTP

| Código | Significado |
|--------|-------------|
| 200 | Solicitud exitosa |
| 201 | Recurso creado exitosamente |
| 401 | No autenticado / Token inválido |
| 403 | Acceso denegado / Cuenta bloqueada |
| 404 | Recurso no encontrado |
| 422 | Error de validación |
| 500 | Error interno del servidor |

---

## Estructura de Respuestas

### Respuesta exitosa
```json
{
    "success": true,
    "message": "Descripción de la operación",
    "data": { ... }
}
```

### Respuesta con error
```json
{
    "success": false,
    "message": "Descripción del error",
    "errors": {
        "campo": ["Mensaje de error específico"]
    }
}
```

---

## Ejemplos de Uso

### cURL

**Login:**
```bash
curl -X POST https://tu-dominio.com/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"usuario@ejemplo.com","password":"contraseña"}'
```

**Obtener usuario autenticado:**
```bash
curl -X GET https://tu-dominio.com/api/v1/auth/me \
  -H "Accept: application/json" \
  -H "Authorization: Bearer 1|abc123..."
```

### JavaScript (Fetch)

```javascript
// Login
const login = async (email, password) => {
    const response = await fetch('https://tu-dominio.com/api/v1/auth/login', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        body: JSON.stringify({ email, password, device_name: 'mi-app' })
    });
    
    const data = await response.json();
    
    if (data.success) {
        // Guardar el token
        localStorage.setItem('api_token', data.data.token);
        return data.data.user;
    } else {
        throw new Error(data.message);
    }
};

// Petición autenticada
const getMe = async () => {
    const token = localStorage.getItem('api_token');
    
    const response = await fetch('https://tu-dominio.com/api/v1/auth/me', {
        headers: {
            'Accept': 'application/json',
            'Authorization': `Bearer ${token}`,
        }
    });
    
    return await response.json();
};
```

### PHP

```php
<?php

// Login
$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => 'https://tu-dominio.com/api/v1/auth/login',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        'email' => 'usuario@ejemplo.com',
        'password' => 'contraseña',
        'device_name' => 'mi-app-php'
    ]),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json',
    ],
]);

$response = curl_exec($curl);
$data = json_decode($response, true);

if ($data['success']) {
    $token = $data['data']['token'];
    // Usar el token para peticiones posteriores
}

curl_close($curl);
```

---

## Notas de Implementación

### Seguridad

- Los tokens no expiran automáticamente (pueden revocarse manualmente)
- Cada dispositivo/aplicación puede tener su propio token usando `device_name`
- Las cuentas bloqueadas, pendientes o rechazadas no pueden obtener tokens

### Estados de Usuario

| Estado | Descripción |
|--------|-------------|
| `pending` | Cuenta en revisión por administrador |
| `approved` | Cuenta activa y puede autenticarse |
| `rejected` | Solicitud rechazada |

### Roles de Usuario

| Rol | Descripción |
|-----|-------------|
| `admin` | Administrador del sistema |
| `user` | Usuario regular |
| `invitado` | Acceso limitado |

---

## Changelog

### v1.0.0 (Febrero 2026)
- ✅ Endpoint de login (`POST /api/v1/auth/login`)
- ✅ Endpoint de logout (`POST /api/v1/auth/logout`)
- ✅ Endpoint para obtener usuario (`GET /api/v1/auth/me`)
- ✅ Endpoint para validar token (`POST /api/v1/auth/validate-token`)
- ✅ Endpoint para renovar token (`POST /api/v1/auth/refresh`)

---

## Próximos Endpoints (Planificados)

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `/api/v1/usuarios` | GET | Listar usuarios |
| `/api/v1/empleados` | GET | Listar empleados |
| `/api/v1/tickets` | GET/POST | Gestión de tickets |

---

## Contacto

Para reportar problemas o solicitar nuevos endpoints, contactar al equipo de Sistemas TI.
