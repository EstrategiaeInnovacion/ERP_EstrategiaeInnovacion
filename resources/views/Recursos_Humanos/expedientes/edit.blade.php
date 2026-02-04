@extends('layouts.erp')
@section('title','Editar Expediente')
@section('content')
<main class="max-w-3xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-xl font-bold text-slate-900">Editar Expediente</h1>
        <a href="{{ route('rh.expedientes.index') }}" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">← Volver</a>
    </div>

    <form method="POST" action="{{ route('rh.expedientes.update',$empleado) }}" class="space-y-6 rounded-3xl border border-blue-100 bg-white/90 backdrop-blur p-6 shadow">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
                <label class="text-xs font-semibold text-slate-600">Nombre</label>
                <input type="text" value="{{ $empleado->nombre }}" disabled class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-600">Correo</label>
                <input type="text" value="{{ $empleado->correo }}" disabled class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm" />
            </div>
            <div>
                <label for="id_empleado" class="text-xs font-semibold text-slate-600">ID Empleado</label>
                <input type="text" id="id_empleado" name="id_empleado" value="{{ old('id_empleado',$empleado->id_empleado) }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-400 focus:ring-0" />
                @error('id_empleado')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="area" class="text-xs font-semibold text-slate-600">Área</label>
                <input type="text" id="area" name="area" value="{{ old('area',$empleado->area) }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-400 focus:ring-0" />
                @error('area')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="text-right">
            <button class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-2 text-sm font-semibold text-white shadow hover:from-blue-700 hover:to-blue-800">Guardar cambios</button>
        </div>
    </form>
</main>
@endsection