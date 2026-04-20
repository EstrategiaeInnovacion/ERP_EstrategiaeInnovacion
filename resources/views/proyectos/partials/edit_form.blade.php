<div>
    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Nombre *</label>
    <input type="text" name="nombre" value="{{ $proyecto->nombre }}" class="w-full rounded-lg border-slate-300 text-sm focus:ring-indigo-500 py-2.5" required>
</div>

<div>
    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Descripción</label>
    <textarea name="descripcion" rows="2" class="w-full rounded-lg border-slate-300 text-sm focus:ring-indigo-500 py-2.5">{{ $proyecto->descripcion }}</textarea>
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Fecha Inicio *</label>
        <input type="date" name="fecha_inicio" value="{{ $proyecto->fecha_inicio }}" class="w-full rounded-lg border-slate-300 text-sm py-2.5" required>
    </div>
    <div>
        <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Fecha Fin *</label>
        <input type="date" name="fecha_fin" value="{{ $proyecto->fecha_fin }}" class="w-full rounded-lg border-slate-300 text-sm py-2.5" required>
    </div>
</div>

<div>
    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Recurrencia de Juntas *</label>
    <select name="recurrencia" class="w-full rounded-lg border-slate-300 text-sm py-2.5" required>
        <option value="mensual" {{ $proyecto->recurrencia == 'mensual' ? 'selected' : '' }}>Mensual</option>
        <option value="quincenal" {{ $proyecto->recurrencia == 'quincenal' ? 'selected' : '' }}>Quincenal</option>
        <option value="semanal" {{ $proyecto->recurrencia == 'semanal' ? 'selected' : '' }}>Semanal</option>
    </select>
</div>

<div>
    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Notas</label>
    <textarea name="notas" rows="2" class="w-full rounded-lg border-slate-300 text-sm py-2.5">{{ $proyecto->notas }}</textarea>
</div>

<div>
    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Asignar Usuarios</label>
    @if($usuarios->count() > 0)
        <div class="max-h-32 overflow-y-auto border border-slate-200 rounded-lg p-2 space-y-1 bg-slate-50">
            @foreach($usuarios as $u)
                <label class="flex items-center gap-2 p-1 hover:bg-slate-100 rounded cursor-pointer">
                    <input type="checkbox" name="usuarios[]" value="{{ $u->id }}" {{ in_array($u->id, $usuariosAsignados) ? 'checked' : '' }} class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm text-slate-700">{{ $u->name }}</span>
                </label>
            @endforeach
        </div>
    @else
        <p class="text-xs text-slate-400">No hay usuarios disponibles</p>
    @endif
</div>

<div>
    <label class="block text-xs font-bold text-cyan-600 uppercase mb-1.5">Asignar Responsables de TI</label>
    @php
        $responsablesAsignados = $proyecto->responsablesTi()->pluck('users.id')->toArray();
        $usuariosTi = \App\Models\User::ti()->whereHas('empleado', fn($q) => $q->where('es_activo', true))->orderBy('name')->get();
    @endphp
    @if($usuariosTi->count() > 0)
        <div class="max-h-32 overflow-y-auto border border-cyan-200 rounded-lg p-2 space-y-1 bg-cyan-50">
            @foreach($usuariosTi as $u)
                <label class="flex items-center gap-2 p-1 hover:bg-cyan-100 rounded cursor-pointer">
                    <input type="checkbox" name="responsables_ti[]" value="{{ $u->id }}" {{ in_array($u->id, $responsablesAsignados) ? 'checked' : '' }} class="rounded border-cyan-300 text-cyan-600 focus:ring-cyan-500">
                    <span class="text-sm text-slate-700">{{ $u->name }}</span>
                    <span class="text-xs text-cyan-500">({{ $u->empleado->posicion ?? 'TI' }})</span>
                </label>
            @endforeach
        </div>
    @else
        <p class="text-xs text-slate-400">No hay usuarios de TI disponibles</p>
    @endif
</div>