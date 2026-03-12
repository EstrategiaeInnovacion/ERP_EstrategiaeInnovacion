<x-mail::message>
# Restablecimiento de Contraseña

**Hola {{ $user->name ?? 'Usuario' }},**

Recibes este correo electrónico porque hemos recibido una solicitud de restablecimiento de contraseña para tu cuenta en el ERP de {{ config('app.name') }}.

<x-mail::button :url="$url">
Restablecer Contraseña
</x-mail::button>

Este enlace de restablecimiento de contraseña expirará en {{ config('auth.passwords.'.config('auth.defaults.passwords').'.expire') }} minutos.

Si tú no solicitaste un restablecimiento de contraseña, no es necesario realizar ninguna otra acción.

Atentamente,<br>
El equipo de TI<br>
{{ config('app.name') }}
</x-mail::message>
