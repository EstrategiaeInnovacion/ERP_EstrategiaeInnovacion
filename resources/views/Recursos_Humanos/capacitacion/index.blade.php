@extends('layouts.erp')

@section('content')
<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Centro de Capacitación</h1>
            <p class="mt-1 text-gray-500">Material educativo y tutoriales de la empresa.</p>
        </div>
        {{-- Botón visible solo para RH para ir a gestionar --}}
        @if(Auth::user()->role === 'admin' || (isset(Auth::user()->empleado) && str_contains(strtolower(Auth::user()->empleado->posicion), 'rh')))
            <a href="{{ route('rh.capacitacion.manage') }}" class="px-4 py-2 bg-gray-800 text-white rounded-md hover:bg-gray-700 text-sm font-bold">
                Gestionar Videos (RH)
            </a>
        @endif
    </div>

    @if($videos->isEmpty())
        <div class="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No hay videos disponibles</h3>
            <p class="mt-1 text-sm text-gray-500">Vuelve más tarde para ver nuevo contenido.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($videos as $video)
                <a href="{{ route('capacitacion.show', $video->id) }}" class="group block bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden border border-gray-200">
                    <div class="aspect-w-16 aspect-h-9 bg-gray-200 relative">
                        {{-- Placeholder visual para el video --}}
                        <div class="absolute inset-0 flex items-center justify-center bg-gray-800 group-hover:bg-gray-700 transition">
                            <svg class="w-12 h-12 text-white opacity-80 group-hover:scale-110 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="p-4">
                        <h3 class="text-lg font-bold text-gray-900 group-hover:text-indigo-600 transition-colors">{{ $video->titulo }}</h3>
                        <p class="mt-1 text-sm text-gray-500 line-clamp-2">{{ $video->descripcion }}</p>
                        <div class="mt-4 flex items-center text-xs text-gray-400">
                            <span>Subido el {{ $video->created_at->format('d M, Y') }}</span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection