<?php

namespace App\Http\Controllers\RH;

use App\Http\Controllers\Controller;
use App\Models\Empleado;
use App\Models\EmpleadoDocumento;
use App\Models\EmpleadoBaja;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Str;

class ExpedienteController extends Controller
{
    /**
     * Listado de expedientes con paginación y buscador.
     */
    public function index(Request $request)
    {
        $query = Empleado::query();

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('id_empleado', 'like', "%{$search}%")
                  ->orWhere('posicion', 'like', "%{$search}%");
            });
        }

        // Filtro por estado activo/baja
        $status = $request->input('status', 'todos');
        if ($status === 'activos') {
            $query->where('es_activo', true);
        }
        elseif ($status === 'bajas') {
            $query->where('es_activo', false);
        }

        // Usamos 'with' para traer los documentos y calcular el % óptimamente
        $empleados = $query->with('documentos')->paginate(12)->withQueryString();

        return view('Recursos_Humanos.expedientes.index', compact('empleados', 'status'));
    }

    /**
     * Ver detalle de un expediente individual.
     */
    public function show($id)
    {
        $empleado = Empleado::with('documentos')->findOrFail($id);

        // Agrupamos documentos por categoría para la vista
        $docsGrouped = $empleado->documentos->groupBy('categoria');

        // Obtenemos la checklist directamente del Modelo (DRY - Fuente de Verdad Única)
        // Asegúrate de haber implementado el método estático getRequisitos en tu Modelo Empleado
        // Si no lo tienes, descomenta la lógica manual abajo.
        $checklistDocs = Empleado::getRequisitos($empleado->es_practicante);

        /* // Lógica manual (Legacy) si no has actualizado el Modelo:
         if ($empleado->es_practicante) {
         $checklistDocs = ['INE', 'CURP', 'Comprobante de Domicilio', 'Estado de Cuenta', 'Formato ID', 'Contrato'];
         } else {
         $checklistDocs = ['INE', 'CURP', 'Comprobante de Domicilio', 'NSS', 'Titulo', 'Constancia de Situacion Fiscal', 'Formato ID', 'Contrato'];
         }
         */

        return view('Recursos_Humanos.expedientes.show', compact('empleado', 'docsGrouped', 'checklistDocs'));
    }

    /**
     * Actualizar datos generales del empleado.
     */
    public function update(Request $request, $id)
    {
        $empleado = Empleado::findOrFail($id);

        // Lógica para cambiar entre Practicante / Empleado
        if ($request->has('toggle_practicante')) {
            $empleado->es_practicante = $request->boolean('es_practicante');
            $empleado->save();

            return back()->with('success', 'Tipo de expediente actualizado correctamente.');
        }

        // Validar que id_empleado no pueda ser vacío o nulo
        // Si viene vacío, mantener el valor actual
        $idEmpleado = $request->input('id_empleado');
        if (empty($idEmpleado)) {
            $request->merge(['id_empleado' => $empleado->id_empleado]);
        }

        // Actualización estándar de campos (excluir user_id para evitar romper el vínculo empleado-usuario)
        $empleado->update($request->except(['user_id', '_token', '_method']));

        return back()->with('success', 'Información actualizada.');
    }

    public function uploadDocument(Request $request, $empleadoId)
    {
        // Validación inicial
        $request->validate([
            'documento' => 'required|file|mimes:pdf,jpg,png,jpeg,xlsx,xls,csv,doc,docx|max:10240',
            'categoria' => 'required|string',
            'fecha_vencimiento' => 'nullable|date'
        ]);

        $nombreFinal = $request->nombre;

        // Si seleccionó "Otro", validamos que haya escrito algo en el manual
        if ($request->nombre === 'Otro') {
            $request->validate([
                'nombre_manual' => 'required|string|max:150'
            ]);
            $nombreFinal = $request->nombre_manual;
        }
        else {
            // Si no es "Otro", el nombre debe venir del select
            $request->validate([
                'nombre' => 'required|string'
            ]);
        }

        $empleado = Empleado::findOrFail($empleadoId);
        $file = $request->file('documento');

        // Limpiamos el nombre del archivo
        $filename = Str::slug($nombreFinal) . '_' . time() . '.' . $file->getClientOriginalExtension();

        $path = $file->storeAs(
            "expedientes/{$empleado->id}",
            $filename,
            'local'
        );

        EmpleadoDocumento::create([
            'empleado_id' => $empleado->id,
            'nombre' => $nombreFinal,
            'categoria' => $request->categoria,
            'ruta_archivo' => $path,
            'fecha_vencimiento' => $request->fecha_vencimiento
        ]);

        return back()->with('success', 'Documento archivado correctamente.');
    }

    /**
     * Eliminar documento.
     */
    public function deleteDocument($id)
    {
        $doc = EmpleadoDocumento::findOrFail($id);

        if (Storage::disk('local')->exists($doc->ruta_archivo)) {
            Storage::disk('local')->delete($doc->ruta_archivo);
        }

        $doc->delete();

        return back()->with('success', 'Documento eliminado.');
    }

    /**
     * [COMBO SUPREMO]
     * Importar "Formato ID" (Excel):
     * 1. Lo guarda como documento en el expediente (Sube la barra de %)
     * 2. Lee los datos internos para actualizar el perfil del empleado.
     */
    public function importFormatoId(Request $request, $id)
    {
        // 1. Configuración para archivos pesados
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 300);

        $request->validate([
            'archivo_excel' => 'required|file|mimes:xlsx,xls,csv|max:10240'
        ]);

        $empleado = Empleado::findOrFail($id);
        $file = $request->file('archivo_excel');

        // ---------------------------------------------------------
        // PASO A: GUARDAR EL ARCHIVO COMO DOCUMENTO
        // ---------------------------------------------------------

        $filename = 'Formato_ID_' . time() . '.' . $file->getClientOriginalExtension();

        $path = $file->storeAs(
            "expedientes/{$empleado->id}",
            $filename,
            'local'
        );

        // Registramos (o actualizamos) que ya entregó el "Formato ID"
        EmpleadoDocumento::updateOrCreate(
        [
            'empleado_id' => $empleado->id,
            'nombre' => 'Formato ID', // Debe coincidir con la lista de requisitos
        ],
        [
            'categoria' => 'Interno',
            'ruta_archivo' => $path,
            'fecha_vencimiento' => null
        ]
        );

        // ---------------------------------------------------------
        // PASO B: LEER DATOS Y ACTUALIZAR PERFIL (Inteligente)
        // ---------------------------------------------------------
        try {
            $spreadsheet = IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            $data = [];
            $camposEncontrados = 0;

            foreach ($rows as $row) {
                // Si la columna A está vacía, saltamos
                if (empty($row[0]))
                    continue;

                $rawLabel = $row[0];
                $value = $row[1] ?? null;

                // Normalizamos etiqueta: "Teléfono Celular" -> "telefono-celular"
                $slug = Str::slug($rawLabel);

                // Si no hay valor o dice "No llenar", saltamos
                if (!$value || Str::contains(Str::lower($value), ['no llenar', 'rh', 'administracion']))
                    continue;

                // --- MAPEO INTELIGENTE DE CAMPOS ---

                // Dirección
                if (Str::contains($slug, ['direccion', 'domicilio', 'calle'])) {
                    $data['direccion'] = $value;
                }
                // Ciudad
                elseif (Str::contains($slug, ['ciudad', 'municipio'])) {
                    $data['ciudad'] = $value;
                }
                // Estado
                elseif (Str::contains($slug, ['estado', 'entidad'])) {
                    $data['estado_federativo'] = $value;
                }
                // CP
                elseif (Str::contains($slug, ['postal', 'cp', 'zip'])) {
                    $data['codigo_postal'] = $value;
                }
                // Teléfono Celular
                elseif (Str::contains($slug, ['celular', 'movil', 'whatsapp'])) {
                    $data['telefono'] = $value;
                }
                // Teléfono Casa
                elseif (Str::contains($slug, ['casa', 'fijo', 'hogar'])) {
                    $data['telefono_casa'] = $value;
                }
                // Alergias
                elseif (Str::contains($slug, ['alergia'])) {
                    $data['alergias'] = $value;
                }
                // Enfermedades
                elseif (Str::contains($slug, ['enfermedad', 'cronica', 'padecimiento'])) {
                    $data['enfermedades_cronicas'] = $value;
                }
                // Contacto Emergencia (Nombre)
                elseif (Str::contains($slug, ['emergencia']) && !Str::contains($slug, ['numero', 'telefono', 'celular'])) {
                    $data['contacto_emergencia_nombre'] = $value;
                }
                // Contacto Emergencia (Número)
                elseif (Str::contains($slug, ['emergencia']) && Str::contains($slug, ['numero', 'telefono', 'celular'])) {
                    $data['contacto_emergencia_numero'] = $value;
                }
                // Parentesco
                elseif (Str::contains($slug, ['parentesco', 'relacion'])) {
                    $data['contacto_emergencia_parentesco'] = $value;
                }
            }

            // Actualizamos al empleado si encontramos datos
            if (count($data) > 0) {
                $empleado->update($data);
                $msg = '¡Proceso Exitoso! Documento archivado y ' . count($data) . ' datos del perfil actualizados.';
            }
            else {
                $msg = 'Documento archivado correctamente. (Nota: No se detectaron datos nuevos para actualizar en el perfil).';
            }

            return back()->with('success', $msg);

        }
        catch (\Exception $e) {
            // Si falla la lectura, al menos el archivo ya se guardó
            return back()->with('warning', 'El archivo se guardó en el expediente, pero hubo un error leyendo los datos internos: ' . $e->getMessage());
        }
    }

    public function downloadDocument($id)
    {
        $doc = EmpleadoDocumento::findOrFail($id);

        // Verificamos si existe en el disco privado
        if (!Storage::disk('local')->exists($doc->ruta_archivo)) {
            abort(404, 'El documento no existe.');
        }

        // Retorna el archivo para descarga/visualización
        return Storage::disk('local')->response($doc->ruta_archivo);
    }

    /**
     * Dar de baja a un empleado desde el módulo RH.
     */
    public function darDeBaja(Request $request, $id)
    {
        $data = $request->validate([
            'motivo_baja' => 'required|string|max:255',
            'observaciones' => 'nullable|string|max:1000',
        ]);

        $empleado = Empleado::findOrFail($id);

        if (!$empleado->es_activo) {
            return back()->with('info', 'Este empleado ya está dado de baja.');
        }

        // Registrar la baja
        EmpleadoBaja::create([
            'empleado_id' => $empleado->id,
            'user_id' => $empleado->user_id,
            'nombre' => $empleado->nombre,
            'correo' => $empleado->correo,
            'motivo_baja' => $data['motivo_baja'],
            'fecha_baja' => now()->toDateString(),
            'observaciones' => $data['observaciones'],
        ]);

        // Desactivar empleado
        $empleado->update(['es_activo' => false]);

        // Desactivar cuenta de usuario asociada
        if ($empleado->user_id) {
            User::where('id', $empleado->user_id)->update([
                'status' => User::STATUS_REJECTED,
                'rejected_at' => now(),
            ]);
        }

        return back()->with('success', $empleado->nombre . ' ha sido dado(a) de baja exitosamente.');
    }

    /**
     * Reactivar a un empleado dado de baja.
     */
    public function reactivar($id)
    {
        $empleado = Empleado::findOrFail($id);

        if ($empleado->es_activo) {
            return back()->with('info', 'Este empleado ya está activo.');
        }

        // Reactivar empleado
        $empleado->update(['es_activo' => true]);

        // Reactivar cuenta de usuario asociada
        if ($empleado->user_id) {
            User::where('id', $empleado->user_id)->update([
                'status' => User::STATUS_APPROVED,
                'approved_at' => now(),
                'rejected_at' => null,
            ]);
        }

        // Eliminar registro de baja
        EmpleadoBaja::where('empleado_id', $empleado->id)->delete();

        return back()->with('success', $empleado->nombre . ' ha sido reactivado(a) exitosamente.');
    }
}