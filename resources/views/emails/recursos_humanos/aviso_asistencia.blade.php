@component('mail::message')
# Aviso Oficial de Recursos Humanos

Estimado/a **{{ $aviso->empleado->nombre ?? 'Colaborador' }}**,

Por medio del presente, el área de **Recursos Humanos** le notifica lo siguiente:

@component('mail::panel')
**Tipo de aviso:** {{ ucfirst($aviso->tipo) }}

**Periodo:** {{ $aviso->periodo }}

@if($aviso->cantidad_incidencias > 0)
**Número de incidencias:** {{ $aviso->cantidad_incidencias }}
@endif

**Mensaje:**

{{ $aviso->mensaje }}
@endcomponent

Este aviso queda registrado en el sistema. En caso de cualquier duda o aclaración, favor de comunicarse directamente con el área de Recursos Humanos.

Atentamente,

**{{ $aviso->enviadoPor->name ?? 'Recursos Humanos' }}**
Área de Recursos Humanos

@endcomponent
