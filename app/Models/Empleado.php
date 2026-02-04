<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon; // <--- Importante para fechas

class Empleado extends Model
{
    use HasFactory;

    // ... (Mantén tu código existente de $table, $fillable y relaciones) ...
    protected $table = 'empleados';

    protected $fillable = [
        'user_id',
        'nombre',
        'correo',
        'area',
        'id_empleado',
        'es_activo',
        'subdepartamento_id',
        'posicion',
        'es_practicante',
        'telefono',
        'direccion',
        'correo_personal',
        'foto_path',
        'supervisor_id',
        'ciudad', 'estado_federativo', 'codigo_postal', 'telefono_casa',
        'alergias', 'enfermedades_cronicas',
        'contacto_emergencia_nombre', 'contacto_emergencia_numero', 'contacto_emergencia_parentesco',
        'rfc', 'curp', 'nss'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(Empleado::class, 'supervisor_id');
    }

    public function subordinados()
    {
        return $this->hasMany(Empleado::class, 'supervisor_id');
    }

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class, 'empleado_id');
    }

    public function documentos()
    {
        return $this->hasMany(\App\Models\EmpleadoDocumento::class);
    }

    // --- LÓGICA DE NEGOCIO ---

    public static function getRequisitos($esPracticante)
    {
        if ($esPracticante) {
            return ['INE', 'CURP', 'Comprobante de Domicilio', 'Estado de Cuenta', 'Formato ID', 'Contrato'];
        }
        return ['INE', 'CURP', 'Comprobante de Domicilio', 'NSS', 'Titulo', 'Constancia de Situacion Fiscal', 'Formato ID', 'Contrato'];
    }

    public function getPorcentajeExpedienteAttribute()
    {
        // ... (Tu lógica existente de porcentaje se mantiene igual) ...
        $datosRequeridos = ['nombre', 'correo', 'telefono', 'direccion', 'contacto_emergencia_numero', 'contacto_emergencia_nombre'];
        $docsRequeridos = self::getRequisitos($this->es_practicante);

        if ($this->es_practicante) {
            $datosRequeridos[] = 'curp';
        } else {
            $datosRequeridos[] = 'nss'; $datosRequeridos[] = 'rfc'; $datosRequeridos[] = 'curp';
        }

        $puntosPosibles = count($docsRequeridos) + count($datosRequeridos);
        $puntosObtenidos = 0;

        foreach ($datosRequeridos as $campo) {
            if (!empty($this->$campo)) $puntosObtenidos++;
        }

        $docsSubidos = $this->documentos->map(fn($doc) => Str::lower($doc->nombre));

        foreach ($docsRequeridos as $req) {
            $reqLower = Str::lower($req);
            if ($req === 'Titulo') {
                if ($docsSubidos->contains(fn($d) => Str::contains($d, ['titulo', 'cedula', 'pasante', 'profesional']))) $puntosObtenidos++;
            } elseif ($req === 'NSS') {
                if ($docsSubidos->contains(fn($d) => Str::contains($d, ['nss', 'imss', 'seguro']))) $puntosObtenidos++;
            } else {
                if ($docsSubidos->contains(fn($d) => Str::contains($d, $reqLower))) $puntosObtenidos++;
            }
        }

        if ($puntosPosibles == 0) return 0;
        return round(($puntosObtenidos / $puntosPosibles) * 100);
    }

    /**
     * NUEVO: Calcula el estado de alerta del expediente.
     * Retorna un array con 'status' (texto), 'color' (clase tailwind base) e 'icon'.
     */
    public function getAlertaExpedienteAttribute()
    {
        // 1. Prioridad: Documentos Vencidos (Rojo Crítico)
        $hoy = Carbon::now();
        $docsVencidos = $this->documentos->filter(function($doc) use ($hoy) {
            return $doc->fecha_vencimiento && $doc->fecha_vencimiento->lt($hoy);
        });

        if ($docsVencidos->count() > 0) {
            return [
                'status' => 'Vencido',
                'bg' => 'bg-red-100',
                'text' => 'text-red-700',
                'border' => 'border-red-200',
                'dot' => 'bg-red-500',
                'msg' => $docsVencidos->count() . ' documento(s) caducado(s)'
            ];
        }

        // 2. Prioridad: Documentos por Vencer (Amarillo Alerta)
        $limiteAlerta = Carbon::now()->addDays(30);
        $docsPorVencer = $this->documentos->filter(function($doc) use ($hoy, $limiteAlerta) {
            return $doc->fecha_vencimiento && $doc->fecha_vencimiento->between($hoy, $limiteAlerta);
        });

        if ($docsPorVencer->count() > 0) {
            return [
                'status' => 'Por Vencer',
                'bg' => 'bg-yellow-50',
                'text' => 'text-yellow-700',
                'border' => 'border-yellow-200',
                'dot' => 'bg-yellow-400',
                'msg' => 'Documentación próxima a expirar'
            ];
        }

        // 3. Prioridad: Expediente Incompleto (Naranja/Rojo)
        // Usamos el atributo que ya calculamos
        if ($this->porcentaje_expediente < 100) {
            return [
                'status' => 'Incompleto',
                'bg' => 'bg-orange-50',
                'text' => 'text-orange-700',
                'border' => 'border-orange-200',
                'dot' => 'bg-orange-500',
                'msg' => 'Faltan datos o documentos'
            ];
        }

        // 4. Todo OK (Verde)
        return [
            'status' => 'Vigente',
            'bg' => 'bg-emerald-50',
            'text' => 'text-emerald-700',
            'border' => 'border-emerald-200',
            'dot' => 'bg-emerald-500',
            'msg' => 'Expediente al día'
        ];
    }
}