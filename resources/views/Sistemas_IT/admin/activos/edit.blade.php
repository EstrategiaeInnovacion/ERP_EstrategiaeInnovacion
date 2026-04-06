@extends('layouts.master')

@section('title', 'Editar Activo IT')

@section('content')
<div class="min-h-screen bg-slate-50 pb-12">

    {{-- Header --}}
    <div class="bg-white border-b border-slate-200 mb-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center gap-3 mb-1">
                <a href="{{ route('admin.activos.index') }}"
                   class="text-slate-400 hover:text-indigo-600 transition-colors text-sm font-medium">
                    Activos IT
                </a>
                <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <a href="{{ route('admin.activos.show', $dispositivo->uuid) }}"
                   class="text-slate-400 hover:text-indigo-600 transition-colors text-sm font-medium truncate max-w-[200px]">
                    {{ $dispositivo->name }}
                </a>
                <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="text-slate-600 text-sm font-medium">Editar</span>
            </div>
            <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Editar Dispositivo</h1>
            <p class="text-slate-500 mt-1">Modifica los datos del equipo en el inventario IT.</p>
        </div>
    </div>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

        @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 rounded-2xl px-5 py-4 text-red-700 text-sm font-medium">
            {{ session('error') }}
        </div>
        @endif

        <form method="POST" action="{{ route('admin.activos.update', $dispositivo->uuid) }}"
              class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden divide-y divide-slate-100">
            @csrf
            @method('PUT')

            {{-- Datos del dispositivo --}}
            <div class="p-8">
                <h2 class="text-base font-bold text-slate-700 mb-5 flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7"/>
                    </svg>
                    Información del equipo
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="sm:col-span-2">
                        <label for="name" class="block text-sm font-semibold text-slate-700 mb-1.5">
                            Nombre <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="name" name="name"
                               value="{{ old('name', $dispositivo->name) }}"
                               class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition @error('name') border-red-400 @enderror">
                        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="brand" class="block text-sm font-semibold text-slate-700 mb-1.5">Marca</label>
                        <input type="text" id="brand" name="brand"
                               value="{{ old('brand', $dispositivo->brand) }}"
                               class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition">
                    </div>

                    <div>
                        <label for="model" class="block text-sm font-semibold text-slate-700 mb-1.5">Modelo</label>
                        <input type="text" id="model" name="model"
                               value="{{ old('model', $dispositivo->model) }}"
                               class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition">
                    </div>

                    <div>
                        <label for="serial_number" class="block text-sm font-semibold text-slate-700 mb-1.5">
                            Número de serie <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="serial_number" name="serial_number"
                               value="{{ old('serial_number', $dispositivo->serial_number) }}"
                               class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-mono text-slate-700 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition @error('serial_number') border-red-400 @enderror">
                        @error('serial_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="type" class="block text-sm font-semibold text-slate-700 mb-1.5">
                            Tipo <span class="text-red-500">*</span>
                        </label>
                        <select id="type" name="type"
                                class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition">
                            <option value="computer"   @selected(old('type', $dispositivo->type) === 'computer')>Computadora</option>
                            <option value="peripheral" @selected(old('type', $dispositivo->type) === 'peripheral')>Periférico</option>
                            <option value="printer"    @selected(old('type', $dispositivo->type) === 'printer')>Impresora</option>
                            <option value="other"      @selected(old('type', $dispositivo->type) === 'other')>Otro</option>
                        </select>
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-semibold text-slate-700 mb-1.5">
                            Estado <span class="text-red-500">*</span>
                        </label>
                        <select id="status" name="status"
                                class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition">
                            <option value="available"   @selected(old('status', $dispositivo->status) === 'available')>Disponible</option>
                            <option value="assigned"    @selected(old('status', $dispositivo->status) === 'assigned')>Asignado</option>
                            <option value="maintenance" @selected(old('status', $dispositivo->status) === 'maintenance')>Mantenimiento</option>
                            <option value="broken"      @selected(old('status', $dispositivo->status) === 'broken')>Dañado</option>
                        </select>
                    </div>

                    <div>
                        <label for="purchase_date" class="block text-sm font-semibold text-slate-700 mb-1.5">Fecha de compra</label>
                        <input type="date" id="purchase_date" name="purchase_date"
                               value="{{ old('purchase_date', $dispositivo->purchase_date ? \Carbon\Carbon::parse($dispositivo->purchase_date)->format('Y-m-d') : '') }}"
                               class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition">
                    </div>

                    <div>
                        <label for="warranty_expiration" class="block text-sm font-semibold text-slate-700 mb-1.5">Vencimiento de garantía</label>
                        <input type="date" id="warranty_expiration" name="warranty_expiration"
                               value="{{ old('warranty_expiration', $dispositivo->warranty_expiration ? \Carbon\Carbon::parse($dispositivo->warranty_expiration)->format('Y-m-d') : '') }}"
                               class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition">
                    </div>

                    <div class="sm:col-span-2">
                        <label for="notes" class="block text-sm font-semibold text-slate-700 mb-1.5">Notas</label>
                        <textarea id="notes" name="notes" rows="3"
                                  class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition resize-none">{{ old('notes', $dispositivo->notes) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Credenciales --}}
            <div class="p-8">
                <h2 class="text-base font-bold text-slate-700 mb-1 flex items-center gap-2">
                    <svg class="w-5 h-5 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                    Credenciales del equipo
                    <span class="text-xs font-normal text-slate-400">(dejar en blanco para no modificar)</span>
                </h2>
                <p class="text-xs text-slate-400 mb-5">Las contraseñas se almacenan cifradas. Si dejas un campo vacío, el valor actual se conserva.</p>

                @if($credencial)
                <div class="mb-5 bg-violet-50 border border-violet-100 rounded-xl px-4 py-3 text-xs text-violet-700 font-medium">
                    Ya existen credenciales guardadas para este equipo. Puedes actualizarlas a continuación.
                </div>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label for="cred_username" class="block text-sm font-semibold text-slate-700 mb-1.5">Usuario del SO</label>
                        <input type="text" id="cred_username" name="cred_username"
                               value="{{ old('cred_username', $credencial->username ?? '') }}"
                               class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-mono text-slate-700 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition">
                    </div>
                    <div>
                        <label for="cred_password" class="block text-sm font-semibold text-slate-700 mb-1.5">Contraseña del SO</label>
                        <div class="relative">
                            <input type="password" id="cred_password" name="cred_password"
                                   placeholder="{{ $credencial ? '(sin cambios)' : '••••••••' }}"
                                   class="w-full border border-slate-200 rounded-xl px-4 py-2.5 pr-10 text-sm font-mono text-slate-700 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition">
                            <button type="button" onclick="togglePass('cred_password')"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label for="cred_email" class="block text-sm font-semibold text-slate-700 mb-1.5">Correo del equipo</label>
                        <input type="email" id="cred_email" name="cred_email"
                               value="{{ old('cred_email', $credencial->email ?? '') }}"
                               class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition @error('cred_email') border-red-400 @enderror">
                        @error('cred_email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="cred_email_password" class="block text-sm font-semibold text-slate-700 mb-1.5">Contraseña del correo</label>
                        <div class="relative">
                            <input type="password" id="cred_email_password" name="cred_email_password"
                                   placeholder="{{ $credencial ? '(sin cambios)' : '••••••••' }}"
                                   class="w-full border border-slate-200 rounded-xl px-4 py-2.5 pr-10 text-sm font-mono text-slate-700 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition">
                            <button type="button" onclick="togglePass('cred_email_password')"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Acciones --}}
            <div class="px-8 py-5 bg-slate-50 flex items-center justify-between">
                <a href="{{ route('admin.activos.show', $dispositivo->uuid) }}"
                   class="text-sm text-slate-500 hover:text-slate-700 font-medium transition">
                    ← Cancelar y volver
                </a>
                <button type="submit"
                        class="px-6 py-2.5 bg-amber-600 text-white font-bold text-sm rounded-xl hover:bg-amber-700 transition shadow-lg shadow-amber-200">
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function togglePass(id) {
    const el = document.getElementById(id);
    el.type = el.type === 'password' ? 'text' : 'password';
}
</script>
@endpush
@endsection
