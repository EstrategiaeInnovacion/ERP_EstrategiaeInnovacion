<x-mail::message>
# Aviso Oficial de {{ $aviso->tipo == 'retardos' ? 'Retardos' : ($aviso->tipo == 'faltas' ? 'Faltas' : 'Asistencia') }}

**Estimado/a {{ $aviso->empleado->nombre }},**

El departamento de Recursos Humanos le informa que se han registrado **{{ $aviso->cantidad_incidencias }} incidencias** en su control de asistencia durante el período de **{{ $aviso->periodo }}**.

A continuación, los detalles del aviso:

<x-mail::panel>
{!! nl2br(e($aviso->mensaje)) !!}
</x-mail::panel>

Por favor, ingrese al Portal Corporativo para dar acuse de recibo de esta notificación.

<x-mail::button :url="url('/')">
Ver en el Portal Corporativo
</x-mail::button>

Atentamente,<br>
Recursos Humanos<br>
{{ config('app.name') }}
</x-mail::message>
