@extends('layouts.erp')

@section('content')
<div class="max-w-5xl mx-auto py-8 px-4">
    <div class="mb-4">
        <a href="{{ route('capacitacion.index') }}" class="text-indigo-600 hover:text-indigo-800 font-medium flex items-center">
            &larr; Volver a Capacitaciones
        </a>
    </div>

    <div class="bg-black rounded-xl overflow-hidden shadow-2xl">
        <video controls class="w-full aspect-video" controlsList="nodownload">
            <source src="{{ asset('storage/' . $video->archivo_path) }}" type="video/mp4">
            Tu navegador no soporta la reproducción de video.
        </video>
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
    </div>
</div>
@endsection