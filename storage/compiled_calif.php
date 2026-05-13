<?php $__env->startSection('title', 'Calificaciones - Matriz de Apoyo'); ?>

<?php $__env->startSection('content'); ?>
<div class="min-h-screen bg-slate-50 pb-12">
    <div class="max-w-[1400px] mx-auto sm:px-6 lg:px-8 space-y-6">

        
        <div class="bg-white border-b border-slate-200 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <div class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                        <a href="<?php echo e(route('logistica.index')); ?>" class="hover:text-blue-600 transition-colors">Panel Logística</a>
                        <span>/</span>
                        <a href="<?php echo e(route('logistica.matriz-apoyo')); ?>" class="hover:text-blue-600 transition-colors">Matriz de Apoyo</a>
                        <span>/</span>
                        <span class="text-slate-700 font-medium">Calificaciones</span>
                    </div>
                    <h1 class="text-2xl font-bold text-slate-900">Análisis de Calificaciones</h1>
                    <p class="text-slate-500 mt-1 text-sm">Proveedores mejor valorados por categoría.</p>
                </div>
                <a href="<?php echo e(route('logistica.matriz-apoyo')); ?>"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-xl text-sm font-semibold hover:bg-slate-50 transition">
                    ← Volver a Matriz
                </a>
            </div>
        </div>

        
        <?php
            $totalConCalif = $agentes->count() + $forwarders->count() + $navieras->count() + $arrastres->count();
            $todos = $agentes->concat($forwarders)->concat($navieras)->concat($arrastres);
            $promedio = $totalConCalif > 0 ? round($todos->avg('calificacion'), 1) : 0;
            $cincoEstrellas = $todos->where('calificacion', 5)->count();
        ?>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 text-center">
                <div class="text-3xl font-bold text-slate-800"><?php echo e($totalConCalif); ?></div>
                <div class="text-xs text-slate-500 mt-1 font-medium uppercase tracking-wide">Proveedores calificados</div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 text-center">
                <div class="text-3xl font-bold text-amber-600"><?php echo e($promedio); ?></div>
                <div class="text-xs text-slate-500 mt-1 font-medium uppercase tracking-wide">Calificación promedio</div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 text-center">
                <div class="text-3xl font-bold text-green-600"><?php echo e($cincoEstrellas); ?></div>
                <div class="text-xs text-slate-500 mt-1 font-medium uppercase tracking-wide">Con 5 estrellas</div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 text-center">
                <div class="text-3xl font-bold text-slate-800">4</div>
                <div class="text-xs text-slate-500 mt-1 font-medium uppercase tracking-wide">Categorías</div>
            </div>
        </div>

        
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm px-6 py-4 flex flex-wrap items-center gap-3">
            <span class="text-sm font-semibold text-slate-600">Filtrar calificación:</span>
            <div class="flex gap-2 flex-wrap">
                <button onclick="setFiltro(0)" id="btn-f-0"
                        class="filtro-btn px-4 py-1.5 rounded-xl text-sm font-semibold border transition active-btn">
                    Todas
                </button>
                <?php for($i = 5; $i >= 1; $i--): ?>
                <button onclick="setFiltro(<?php echo e($i); ?>)" id="btn-f-<?php echo e($i); ?>"
                        class="filtro-btn px-4 py-1.5 rounded-xl text-sm font-semibold border transition">
                    <?php echo e(str_repeat('★', $i)); ?><?php echo e(str_repeat('☆', 5 - $i)); ?>

                </button>
                <?php endfor; ?>
            </div>
        </div>

        
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-slate-800 text-lg">Agentes Aduanales</h2>
                    <p class="text-xs text-slate-400 mt-0.5"><?php echo e($agentes->count()); ?> registros con calificación</p>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700 border border-amber-200">
                    Promedio <?php echo e($agentes->count() ? round($agentes->avg('calificacion'), 1) : '—'); ?> ★
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide">Calificación</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide">Agente Aduanal</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide">Razón Social</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide">Cliente</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide">Aduana</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide">Responsabilidad</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide">Contacto</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-cal-agentes">
                        <?php $__empty_1 = true; $__currentLoopData = $agentes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="border-b border-slate-100 hover:bg-amber-50/30 transition-colors cal-row"
                            data-calif="<?php echo e($row->calificacion); ?>">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex gap-0.5">
                                    <?php for($s = 1; $s <= 5; $s++): ?>
                                        <span class="<?php echo e($s <= $row->calificacion ? 'text-amber-400' : 'text-slate-200'); ?> text-lg leading-none">★</span>
                                    <?php endfor; ?>
                                </div>
                                <span class="text-xs text-slate-400"><?php echo e($row->calificacion); ?>/5</span>
                            </td>
                            <td class="px-4 py-3 font-semibold text-slate-800"><?php echo e($row->agente_aduanal); ?></td>
                            <td class="px-4 py-3 text-slate-600"><?php echo e($row->razon_social ?? '—'); ?></td>
                            <td class="px-4 py-3 text-slate-600"><?php echo e($row->cliente ?? '—'); ?></td>
                            <td class="px-4 py-3 text-slate-600"><?php echo e($row->aduana ?? '—'); ?></td>
                            <td class="px-4 py-3 text-slate-600 text-xs"><?php echo e($row->responsabilidad); ?></td>
                            <td class="px-4 py-3 text-xs text-slate-500">
                                <?php if($row->nombre): ?><div><?php echo e($row->nombre); ?></div><?php endif; ?>
                                <?php if($row->correo_electronico): ?><a href="mailto:<?php echo e($row->correo_electronico); ?>" class="text-blue-600 hover:underline"><?php echo e($row->correo_electronico); ?></a><?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400 text-sm">Sin registros calificados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-slate-800 text-lg">Forwarders</h2>
                    <p class="text-xs text-slate-400 mt-0.5"><?php echo e($forwarders->count()); ?> registros con calificación</p>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700 border border-amber-200">
                    Promedio <?php echo e($forwarders->count() ? round($forwarders->avg('calificacion'), 1) : '—'); ?> ★
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide">Calificación</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide">Razón Social</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide">Cliente</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide">Aduana</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide">Responsabilidad</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide">Contacto</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-cal-forwarders">
                        <?php $__empty_1 = true; $__currentLoopData = $forwarders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="border-b border-slate-100 hover:bg-amber-50/30 transition-colors cal-row"
                            data-calif="<?php echo e($row->calificacion); ?>">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex gap-0.5">
                                    <?php for($s = 1; $s <= 5; $s++): ?>
                                        <span class="<?php echo e($s <= $row->calificacion ? 'text-amber-400' : 'text-slate-200'); ?> text-lg leading-none">★</span>
                                    <?php endfor; ?>
                                </div>
                                <span class="text-xs text-slate-400"><?php echo e($row->calificacion); ?>/5</span>
                            </td>
                            <td class="px-4 py-3 font-semibold text-slate-800"><?php echo e($row->razon_social ?? '—'); ?></td>
                            <td class="px-4 py-3 text-slate-600"><?php echo e($row->cliente ?? '—'); ?></td>
                            <td class="px-4 py-3 text-slate-600"><?php echo e($row->aduana ?? '—'); ?></td>
                            <td class="px-4 py-3 text-slate-600 text-xs"><?php echo e($row->responsabilidad); ?></td>
                            <td class="px-4 py-3 text-xs text-slate-500">
                                <?php if($row->nombre): ?><div><?php echo e($row->nombre); ?></div><?php endif; ?>
                                <?php if($row->correo_electronico): ?><a href="mailto:<?php echo e($row->correo_electronico); ?>" class="text-blue-600 hover:underline"><?php echo e($row->correo_electronico); ?></a><?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400 text-sm">Sin registros calificados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-slate-800 text-lg">Navieras</h2>
                    <p class="text-xs text-slate-400 mt-0.5"><?php echo e($navieras->count()); ?> registros con calificación</p>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700 border border-amber-200">
                    Promedio <?php echo e($navieras->count() ? round($navieras->avg('calificacion'), 1) : '—'); ?> ★
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide">Calificación</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide">Razón Social</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide">Cliente</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide">Aduana</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide">Responsabilidad</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide">Contacto</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-cal-navieras">
                        <?php $__empty_1 = true; $__currentLoopData = $navieras; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="border-b border-slate-100 hover:bg-amber-50/30 transition-colors cal-row"
                            data-calif="<?php echo e($row->calificacion); ?>">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex gap-0.5">
                                    <?php for($s = 1; $s <= 5; $s++): ?>
                                        <span class="<?php echo e($s <= $row->calificacion ? 'text-amber-400' : 'text-slate-200'); ?> text-lg leading-none">★</span>
                                    <?php endfor; ?>
                                </div>
                                <span class="text-xs text-slate-400"><?php echo e($row->calificacion); ?>/5</span>
                            </td>
                            <td class="px-4 py-3 font-semibold text-slate-800"><?php echo e($row->razon_social ?? '—'); ?></td>
                            <td class="px-4 py-3 text-slate-600"><?php echo e($row->cliente ?? '—'); ?></td>
                            <td class="px-4 py-3 text-slate-600"><?php echo e($row->aduana ?? '—'); ?></td>
                            <td class="px-4 py-3 text-slate-600 text-xs"><?php echo e($row->responsabilidad); ?></td>
                            <td class="px-4 py-3 text-xs text-slate-500">
                                <?php if($row->nombre): ?><div><?php echo e($row->nombre); ?></div><?php endif; ?>
                                <?php if($row->correo_electronico): ?><a href="mailto:<?php echo e($row->correo_electronico); ?>" class="text-blue-600 hover:underline"><?php echo e($row->correo_electronico); ?></a><?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400 text-sm">Sin registros calificados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-slate-800 text-lg">Arrastres Nacionales</h2>
                    <p class="text-xs text-slate-400 mt-0.5"><?php echo e($arrastres->count()); ?> registros con calificación</p>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700 border border-amber-200">
                    Promedio <?php echo e($arrastres->count() ? round($arrastres->avg('calificacion'), 1) : '—'); ?> ★
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide">Calificación</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide">Razón Social</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide">Cliente</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide">Aduana</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide">Responsabilidad</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide">Contacto</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-cal-arrastres">
                        <?php $__empty_1 = true; $__currentLoopData = $arrastres; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="border-b border-slate-100 hover:bg-amber-50/30 transition-colors cal-row"
                            data-calif="<?php echo e($row->calificacion); ?>">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex gap-0.5">
                                    <?php for($s = 1; $s <= 5; $s++): ?>
                                        <span class="<?php echo e($s <= $row->calificacion ? 'text-amber-400' : 'text-slate-200'); ?> text-lg leading-none">★</span>
                                    <?php endfor; ?>
                                </div>
                                <span class="text-xs text-slate-400"><?php echo e($row->calificacion); ?>/5</span>
                            </td>
                            <td class="px-4 py-3 font-semibold text-slate-800"><?php echo e($row->razon_social ?? '—'); ?></td>
                            <td class="px-4 py-3 text-slate-600"><?php echo e($row->cliente ?? '—'); ?></td>
                            <td class="px-4 py-3 text-slate-600"><?php echo e($row->aduana ?? '—'); ?></td>
                            <td class="px-4 py-3 text-slate-600 text-xs"><?php echo e($row->responsabilidad); ?></td>
                            <td class="px-4 py-3 text-xs text-slate-500">
                                <?php if($row->nombre): ?><div><?php echo e($row->nombre); ?></div><?php endif; ?>
                                <?php if($row->correo_electronico): ?><a href="mailto:<?php echo e($row->correo_electronico); ?>" class="text-blue-600 hover:underline"><?php echo e($row->correo_electronico); ?></a><?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400 text-sm">Sin registros calificados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<style>
.filtro-btn { background: white; border-color: #e2e8f0; color: #64748b; }
.filtro-btn:hover { background: #fef9c3; border-color: #fbbf24; color: #92400e; }
.active-btn { background: #fef3c7 !important; border-color: #f59e0b !important; color: #92400e !important; font-weight: 700; }
</style>

<script>
let filtroActivo = 0;

function setFiltro(calif) {
    filtroActivo = calif;

    document.querySelectorAll('.filtro-btn').forEach(b => b.classList.remove('active-btn'));
    document.getElementById('btn-f-' + calif).classList.add('active-btn');

    document.querySelectorAll('.cal-row').forEach(row => {
        const rc = parseInt(row.dataset.calif);
        row.style.display = (calif === 0 || rc === calif) ? '' : 'none';
    });
}

document.addEventListener('DOMContentLoaded', () => setFiltro(0));
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.erp', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>