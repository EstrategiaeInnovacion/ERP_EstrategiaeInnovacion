<?php

namespace App\Http\Controllers\Logistica;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AduanaImportService;
use App\Models\Logistica\Aduana;
use Illuminate\Support\Facades\Storage;

class AduanaImportController extends Controller
{
    protected $aduanaImportService;

    public function __construct(AduanaImportService $aduanaImportService)
    {
        $this->aduanaImportService = $aduanaImportService;
    }

    /**
     * Importar aduanas desde archivo Word
     */
    public function import(Request $request)
    {
        try {
            // Validar el archivo subido
            $request->validate([
                'file' => 'required|file|mimes:docx,doc,csv,xlsx,xls|max:10240' // Máximo 10MB
            ]);

            // Guardar el archivo temporalmente
            $file = $request->file('file');
            $fileName = 'aduanas_' . time() . '.' . $file->getClientOriginalExtension();
            
            // Usar Storage para manejar los archivos de forma más segura
            $relativePath = 'temp/imports/' . $fileName;
            
            // Guardar el archivo usando Storage
            $stored = Storage::put($relativePath, file_get_contents($file));
            
            if (!$stored) {
                throw new \Exception("Error al guardar el archivo temporal: {$fileName}");
            }
            
            $fullPath = Storage::path($relativePath);

            // Verificar que el archivo se guardó correctamente
            if (!Storage::exists($relativePath)) {
                throw new \Exception("Error: el archivo temporal no se pudo crear correctamente");
            }

            \Log::info("Procesando archivo de aduanas: {$fileName}");

            // Procesar la importación
            $result = $this->aduanaImportService->import($fullPath);

            // Limpiar el archivo temporal solo si el procesamiento fue exitoso
            if (Storage::exists($relativePath)) {
                Storage::delete($relativePath);
            }

            return response()->json($result);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Limpiar archivo temporal en caso de error de validación si existe
            if (isset($relativePath) && Storage::exists($relativePath)) {
                Storage::delete($relativePath);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Archivo inválido: ' . implode(', ', $e->validator->errors()->all()),
                'total_processed' => 0,
                'total_imported' => 0
            ], 422);

        } catch (\Exception $e) {
            // Limpiar archivo temporal en caso de error si existe
            if (isset($relativePath) && Storage::exists($relativePath)) {
                Storage::delete($relativePath);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la importación: ' . $e->getMessage(),
                'total_processed' => 0,
                'total_imported' => 0
            ], 500);
        }
    }

    /**
     * Obtener lista de aduanas
     */
    public function index(Request $request)
    {
        try {
            $query = Aduana::query();

            // Filtro por búsqueda
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('denominacion', 'LIKE', "%{$search}%")
                      ->orWhere('aduana', 'LIKE', "%{$search}%")
                      ->orWhere('patente', 'LIKE', "%{$search}%");
                });
            }

            // Filtro por país
            if ($request->has('pais') && !empty($request->pais)) {
                $query->where('pais', $request->pais);
            }

            // Ordenamiento
            $sortBy = $request->get('sort_by', 'aduana');
            $sortDirection = $request->get('sort_direction', 'asc');
            $query->orderBy($sortBy, $sortDirection);

            // Paginación
            $perPage = $request->get('per_page', 15);
            $aduanas = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'aduanas' => $aduanas,
                'stats' => $this->aduanaImportService->getStats()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las aduanas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar una aduana
     */
    public function destroy($id)
    {
        try {
            $aduana = Aduana::findOrFail($id);
            $aduana->delete();

            return response()->json([
                'success' => true,
                'message' => 'Aduana eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la aduana: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Limpiar todas las aduanas
     */
    public function clear()
    {
        try {
            $count = Aduana::count();
            Aduana::truncate();

            return response()->json([
                'success' => true,
                'message' => "Se eliminaron {$count} aduanas exitosamente",
                'deleted_count' => $count
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar las aduanas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear una nueva aduana
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'aduana' => 'required|string|size:2|regex:/^[0-9]{2}$/',
                'seccion' => 'nullable|string|size:1|regex:/^[0-9]$/',
                'denominacion' => 'required|string|max:255'
            ]);

            // Verificar si ya existe la combinación aduana + sección
            $seccion = $request->seccion ?: '0';
            $existing = Aduana::where('aduana', $request->aduana)
                             ->where('seccion', $seccion)
                             ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe una aduana con ese código y sección'
                ], 422);
            }

            $aduana = Aduana::create([
                'aduana' => $request->aduana,
                'seccion' => $seccion,
                'denominacion' => $request->denominacion
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Aduana creada exitosamente',
                'aduana' => $aduana
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos: ' . implode(', ', $e->validator->errors()->all())
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la aduana: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar una aduana existente
     */
    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'aduana' => 'required|string|size:2|regex:/^[0-9]{2}$/',
                'seccion' => 'nullable|string|size:1|regex:/^[0-9]$/',
                'denominacion' => 'required|string|max:255',
                'patente' => 'nullable|string|max:50',
                'pais' => 'nullable|string|max:10'
            ]);

            $aduana = Aduana::findOrFail($id);
            $seccion = $request->seccion ?: '0';

            // Verificar si ya existe otra aduana con la misma combinación código + sección
            $existing = Aduana::where('aduana', $request->aduana)
                             ->where('seccion', $seccion)
                             ->where('id', '!=', $id)
                             ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe otra aduana con ese código y sección'
                ], 422);
            }

            $aduana->update([
                'aduana' => $request->aduana,
                'seccion' => $seccion,
                'denominacion' => $request->denominacion,
                'patente' => $request->patente,
                'pais' => $request->pais ?: 'MX'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Aduana actualizada exitosamente',
                'aduana' => $aduana
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos: ' . implode(', ', $e->validator->errors()->all())
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la aduana: ' . $e->getMessage()
            ], 500);
        }
    }
}
