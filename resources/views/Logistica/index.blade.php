<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Departamento de Logística') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-400 text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                
                <a href="{{ route('logistica.matriz-seguimiento') }}" class="block group">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-lg transition-shadow duration-300 h-full border-l-4 border-blue-500">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-blue-100 text-blue-500 group-hover:bg-blue-500 group-hover:text-white transition-colors">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                </div>
                                <h3 class="ml-4 text-lg font-bold text-gray-800 group-hover:text-blue-600">Matriz de Seguimiento</h3>
                            </div>
                            <p class="mt-4 text-gray-600 text-sm">
                                Gestión operativa día a día. Control de estatus, fechas críticas y semáforos de cumplimiento.
                            </p>
                        </div>
                    </div>
                </a>

                <a href="{{ route('logistica.pedimentos.index') }}" class="block group">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-lg transition-shadow duration-300 h-full border-l-4 border-indigo-500">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-indigo-100 text-indigo-500 group-hover:bg-indigo-500 group-hover:text-white transition-colors">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                </div>
                                <h3 class="ml-4 text-lg font-bold text-gray-800 group-hover:text-indigo-600">Control de Pedimentos</h3>
                            </div>
                            <p class="mt-4 text-gray-600 text-sm">
                                Registro y seguimiento de pagos, estados de cuenta y consolidación por clave.
                            </p>
                        </div>
                    </div>
                </a>

                <a href="{{ route('logistica.reportes.index') }}" class="block group">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-lg transition-shadow duration-300 h-full border-l-4 border-emerald-500">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-emerald-100 text-emerald-500 group-hover:bg-emerald-500 group-hover:text-white transition-colors">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path></svg>
                                </div>
                                <h3 class="ml-4 text-lg font-bold text-gray-800 group-hover:text-emerald-600">Reportes & KPIs</h3>
                            </div>
                            <p class="mt-4 text-gray-600 text-sm">
                                Análisis de eficiencia, exportación a Excel y métricas de desempeño operativo.
                            </p>
                        </div>
                    </div>
                </a>

                <a href="{{ route('logistica.catalogos') }}" class="block group">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-lg transition-shadow duration-300 h-full border-l-4 border-slate-500">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-slate-100 text-slate-500 group-hover:bg-slate-500 group-hover:text-white transition-colors">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                </div>
                                <h3 class="ml-4 text-lg font-bold text-gray-800 group-hover:text-slate-600">Catálogos</h3>
                            </div>
                            <p class="mt-4 text-gray-600 text-sm">
                                Gestión de Clientes, Agentes Aduanales, Transportes y configuración del sistema.
                            </p>
                        </div>
                    </div>
                </a>

            </div>
        </div>
    </div>
</x-app-layout>