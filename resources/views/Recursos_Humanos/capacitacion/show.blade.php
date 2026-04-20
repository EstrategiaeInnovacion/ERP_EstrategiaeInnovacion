@extends('layouts.erp')

@section('content')
<div class="max-w-5xl mx-auto py-8 px-4">
    <div class="mb-4">
        <a href="{{ route('capacitacion.index') }}" class="text-indigo-600 hover:text-indigo-800 font-medium flex items-center">
            &larr; Volver a Capacitaciones
        </a>
    </div>

    <div class="bg-black rounded-xl overflow-hidden shadow-2xl relative w-full" style="padding-top: 56.25%;"> <!-- Aspect ratio 16:9 -->
        @if($video->isYoutube() && $video->getYoutubeId())
            <iframe 
                class="absolute top-0 left-0 w-full h-full"
                src="https://www.youtube.com/embed/{{ $video->getYoutubeId() }}?rel=0" 
                title="{{ $video->titulo }}"
                frameborder="0" 
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                allowfullscreen>
            </iframe>
        @elseif($video->archivo_path)
            <video controls class="absolute top-0 left-0 w-full h-full" controlsList="nodownload">
                <source src="{{ asset('storage/' . $video->archivo_path) }}" type="video/mp4">
                Tu navegador no soporta la reproducción de video.
            </video>
        @else
            <div class="absolute top-0 left-0 w-full h-full flex items-center justify-center text-white">
                <p>No hay video disponible.</p>
            </div>
        @endif
    </div>

    <div class="mt-6 bg-white p-6 rounded-xl shadow-sm border border-gray-200">
        <h1 class="text-2xl font-bold text-gray-900">{{ $video->titulo }}</h1>
        <div class="mt-2 text-sm text-gray-500">
            Publicado el {{ $video->created_at->format('d/m/Y') }}
        </div>
        <hr class="my-4 border-gray-100">
        <div class="prose max-w-none text-gray-700">
            {{ $video->descripcion }}
        </div>
        @if($video->adjuntos->isNotEmpty())
            <div class="mt-8 border-t pt-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Material de Apoyo y Descargas</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach($video->adjuntos as $adjunto)
                        <a href="{{ asset('storage/' . $adjunto->archivo_path) }}" target="_blank" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 border border-gray-200 transition group">
                            <span class="text-2xl mr-3 group-hover:scale-110 transition">📄</span>
                            <div>
                                <p class="text-sm font-medium text-gray-700 group-hover:text-indigo-600">{{ $adjunto->titulo }}</p>
                                <p class="text-xs text-gray-500">Clic para descargar</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- PANEL DE PERMISOS: solo visible para Admin y RH --}}
        @if(Auth::user()->isAdmin() || Auth::user()->isRh())
            @php
                $puestosLimpios = array_values(array_filter($video->puestos_permitidos ?? [], fn($p) => !empty(trim((string) $p))));
                $usuariosLimpios = array_values(array_filter($video->usuarios_permitidos ?? [], fn($u) => !is_null($u)));
                $esPublico = empty($puestosLimpios) && empty($usuariosLimpios);
            @endphp
            <div class="mt-8 border-t pt-6">
                <div class="flex items-center gap-2 mb-3">
                    <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide">Control de Acceso <span class="text-xs font-normal text-gray-400">(solo visible para Admin / RH)</span></h3>
                    <a href="{{ route('rh.capacitacion.edit', $video->id) }}" class="ml-auto text-xs text-indigo-600 hover:text-indigo-800 font-medium">Editar permisos</a>
                </div>

                @if($esPublico)
                    <div class="flex items-center gap-2 bg-green-50 border border-green-200 rounded-lg px-4 py-3">
                        <svg class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"/></svg>
                        <span class="text-sm font-bold text-green-800">Público</span>
                        <span class="text-xs text-green-600">— Todos los empleados activos pueden ver este video.</span>
                    </div>
                @else
                    <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 space-y-3">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            <span class="text-sm font-bold text-amber-800">Restringido</span>
                        </div>
                        @if(!empty($puestosLimpios))
                            <div>
                                <p class="text-xs font-bold text-gray-600 mb-1">Puestos con acceso:</p>
                                <div class="flex flex-wrap gap-1">
                                    @foreach($puestosLimpios as $puesto)
                                        <span class="bg-white border border-amber-300 text-amber-700 text-xs font-medium px-2 py-0.5 rounded-full">{{ $puesto }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        @if(!empty($usuariosLimpios))
                            <div>
                                <p class="text-xs font-bold text-gray-600 mb-1">Usuarios específicos con acceso:</p>
                                <div class="flex flex-wrap gap-1">
                                    @foreach(\App\Models\User::whereIn('id', $usuariosLimpios)->orderBy('name')->get() as $u)
                                        <span class="bg-white border border-blue-300 text-blue-700 text-xs font-medium px-2 py-0.5 rounded-full">{{ $u->name }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection