<?php

namespace App\Http\Controllers\Logistica;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\PedimentoImportService;
use App\Models\Logistica\Pedimento;
use Illuminate\Support\Facades\Storage;

class PedimentoImportController extends Controller
{
    protected $pedimentoImportService;

    public function __construct(PedimentoImportService $pedimentoImportService)
    {
        $this->pedimentoImportService = $pedimentoImportService;
    }

    /**
     * Importar pedimentos desde archivo Word/Excel/CSV
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
            $fileName = 'pedimentos_' . time() . '.' . $file->getClientOriginalExtension();
            
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

            \Log::info("Procesando archivo de pedimentos: {$fileName}");

            // Procesar la importación
            $result = $this->pedimentoImportService->import($fullPath);

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
     * Obtener lista de pedimentos
     */
    public function index(Request $request)
    {
        try {
            $query = Pedimento::query();

            // Filtro por búsqueda
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('clave', 'like', "%{$search}%")
                      ->orWhere('descripcion', 'like', "%{$search}%")
                      ->orWhere('categoria', 'like', "%{$search}%")
                      ->orWhere('subcategoria', 'like', "%{$search}%");
                });
            }

            // Filtro por categoría
            if ($request->has('categoria') && !empty($request->categoria)) {
                $query->where('categoria', $request->categoria);
            }

            // Filtro por subcategoría
            if ($request->has('subcategoria') && !empty($request->subcategoria)) {
                $query->where('subcategoria', $request->subcategoria);
            }

            $pedimentos = $query->orderBy('clave')->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $pedimentos
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los pedimentos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un pedimento
     */
    public function destroy($id)
    {
        try {
            $pedimento = Pedimento::findOrFail($id);
            $pedimento->delete();

            return response()->json([
                'success' => true,
                'message' => 'Pedimento eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el pedimento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Limpiar todos los pedimentos
     */
    public function clear()
    {
        try {
            $count = Pedimento::count();
            Pedimento::truncate();

            return response()->json([
                'success' => true,
                'message' => "Se eliminaron {$count} pedimentos exitosamente",
                'deleted_count' => $count
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar los pedimentos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un nuevo pedimento
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'clave' => 'required|string|max:10|unique:pedimentos,clave',
                'descripcion' => 'required|string|max:500',
                'categoria' => 'nullable|string|max:255',
                'subcategoria' => 'nullable|string|max:255'
            ]);

            $pedimento = Pedimento::create([
                'clave' => strtoupper(trim($request->clave)),
                'descripcion' => trim($request->descripcion),
                'categoria' => $request->categoria ? trim($request->categoria) : null,
                'subcategoria' => $request->subcategoria ? trim($request->subcategoria) : null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pedimento creado exitosamente',
                'pedimento' => $pedimento
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos: ' . implode(', ', $e->validator->errors()->all())
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el pedimento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un pedimento existente
     */
    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'clave' => 'required|string|max:10|unique:pedimentos,clave,' . $id,
                'descripcion' => 'required|string|max:500',
                'categoria' => 'nullable|string|max:255',
                'subcategoria' => 'nullable|string|max:255'
            ]);

            $pedimento = Pedimento::findOrFail($id);

            $pedimento->update([
                'clave' => strtoupper(trim($request->clave)),
                'descripcion' => trim($request->descripcion),
                'categoria' => $request->categoria ? trim($request->categoria) : null,
                'subcategoria' => $request->subcategoria ? trim($request->subcategoria) : null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pedimento actualizado exitosamente',
                'pedimento' => $pedimento
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos: ' . implode(', ', $e->validator->errors()->all())
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el pedimento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener todas las categorías disponibles
     */
    public function getCategorias()
    {
        try {
            $categorias = Pedimento::getCategorias();
            
            return response()->json([
                'success' => true,
                'data' => $categorias
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las categorías: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener subcategorías por categoría
     */
    public function getSubcategorias(Request $request)
    {
        try {
            $categoria = $request->input('categoria');
            $subcategorias = Pedimento::getSubcategoriasPorCategoria($categoria);
            
            return response()->json([
                'success' => true,
                'data' => $subcategorias
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las subcategorías: ' . $e->getMessage()
            ], 500);
        }
    }
}