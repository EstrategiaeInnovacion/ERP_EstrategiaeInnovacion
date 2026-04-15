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
    <select name="usuarios[]" multiple class="w-full rounded-lg border-slate-300 text-sm py-2.5" size="4">
        @foreach($usuarios as $u)
            <option value="{{ $u->id }}" {{ in_array($u->id, $usuariosAsignados) ? 'selected' : '' }}>{{ $u->name }}</option>
        @endforeach
    </select>
    <p class="text-xs text-slate-400 mt-1">Mantén presionado Ctrl/Cmd para seleccionar varios</p>
</div>