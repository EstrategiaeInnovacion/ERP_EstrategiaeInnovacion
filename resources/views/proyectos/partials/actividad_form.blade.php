<div>
    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Descripción *</label>
    <input type="text" name="nombre_actividad" value="{{ $actividad->nombre_actividad }}" class="w-full rounded-lg border-slate-300 text-sm py-2.5" required>
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Asignar a</label>
        <select name="asignado_a" class="w-full rounded-lg border-slate-300 text-sm py-2.5">
            @foreach($usuariosAsignables as $u)
                <option value="{{ $u->id }}" {{ $actividad->user_id == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Prioridad</label>
        <select name="prioridad" class="w-full rounded-lg border-slate-300 text-sm py-2.5">
            <option value="Media" {{ $actividad->prioridad == 'Media' ? 'selected' : '' }}>Media</option>
            <option value="Alta" {{ $actividad->prioridad == 'Alta' ? 'selected' : '' }}>Alta</option>
            <option value="Baja" {{ $actividad->prioridad == 'Baja' ? 'selected' : '' }}>Baja</option>
        </select>
    </div>
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Área</label>
        <select name="area" class="w-full rounded-lg border-slate-300 text-sm py-2.5">
            @foreach($areas as $area)
                <option value="{{ $area }}" {{ $actividad->area == $area ? 'selected' : '' }}>{{ $area }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Fecha Compromiso</label>
        <input type="date" name="fecha_compromiso" value="{{ $actividad->fecha_compromiso->format('Y-m-d') }}" class="w-full rounded-lg border-slate-300 text-sm py-2.5">
    </div>
</div>

<div>
    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Estatus</label>
    <select name="estatus" class="w-full rounded-lg border-slate-300 text-sm py-2.5">
        <option value="Planeado" {{ $actividad->estatus == 'Planeado' ? 'selected' : '' }}>Planeado</option>
        <option value="En proceso" {{ $actividad->estatus == 'En proceso' ? 'selected' : '' }}>En proceso</option>
        <option value="Completado" {{ $actividad->estatus == 'Completado' ? 'selected' : '' }}>Completado</option>
        <option value="Por Aprobar" {{ $actividad->estatus == 'Por Aprobar' ? 'selected' : '' }}>Por Aprobar</option>
        <option value="Retardo" {{ $actividad->estatus == 'Retardo' ? 'selected' : '' }}>Retardo</option>
    </select>
</div>

<div>
    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Cliente</label>
    <input type="text" name="cliente" value="{{ $actividad->cliente }}" class="w-full rounded-lg border-slate-300 text-sm py-2.5">
</div>

<div>
    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Comentarios</label>
    <textarea name="comentarios" rows="2" class="w-full rounded-lg border-slate-300 text-sm py-2.5">{{ $actividad->comentarios }}</textarea>
</div>