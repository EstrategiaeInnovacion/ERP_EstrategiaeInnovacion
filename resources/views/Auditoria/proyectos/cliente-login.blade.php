<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceso Protegido — Seguimiento de Auditoría</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,850&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-50 text-slate-600 min-h-screen flex flex-col justify-between">
 
    {{-- Fondo Decorativo --}}
    <div class="absolute inset-0 pointer-events-none overflow-hidden z-0">
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-indigo-50/60 blur-3xl rounded-full mix-blend-multiply"></div>
        <div class="absolute top-24 -left-24 w-72 h-72 bg-violet-50/60 blur-3xl rounded-full mix-blend-multiply"></div>
    </div>
 
    {{-- Contenedor Principal --}}
    <div class="relative z-10 flex-grow flex flex-col items-center justify-center p-4">
        <div class="w-full max-w-md bg-white rounded-3xl shadow-xl border border-slate-200/60 overflow-hidden transform transition-all hover:scale-[1.01] duration-300">
            <div class="h-1.5 bg-gradient-to-r from-indigo-500 via-violet-500 to-indigo-500"></div>
            
            <div class="p-8">
                {{-- Logo EI --}}
                <div class="flex justify-center mb-6">
                    <img src="{{ asset('images/logo-ei.png') }}" alt="Estrategia e Innovación Logo" class="h-12 w-auto">
                </div>
                
                <div class="text-center mb-6">
                    <span class="px-2.5 py-0.5 rounded-full bg-indigo-50 text-[10px] font-extrabold uppercase tracking-widest text-indigo-600 border border-indigo-100">Seguimiento Externo</span>
                    <h2 class="mt-4 text-2xl font-extrabold text-slate-800 tracking-tight">Acceso Restringido</h2>
                    <p class="mt-2 text-sm text-slate-400">
                        Esta auditoría del cliente <strong>{{ $proyecto->cliente->nombre }}</strong> está protegida. Ingresa la contraseña asignada por tu coordinador para ver el avance.
                    </p>
                </div>
 
                @if($errors->any())
                    <div class="mb-4 p-3 rounded-xl bg-rose-50 border border-rose-100 text-rose-800 text-xs font-semibold flex items-center gap-2 animate-fade-in-up">
                        <svg class="w-4 h-4 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif
 
                <form action="{{ route('auditoria.publico.password', $token) }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label for="password" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Contraseña de Acceso</label>
                        <div class="relative rounded-xl shadow-sm">
                            <input type="password" name="password" id="password" required autofocus placeholder="Ingresar contraseña..."
                                   class="w-full text-sm border border-slate-200 rounded-xl bg-slate-50/50 py-3 px-4 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:bg-white transition">
                        </div>
                    </div>
 
                    <button type="submit" 
                            class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-center shadow-lg shadow-indigo-100 transition duration-300 text-sm active:scale-95">
                        Ingresar a la Auditoría
                    </button>
                </form>
            </div>
            
            <div class="bg-slate-50 px-8 py-4 border-t border-slate-100 text-center text-[10px] text-slate-400 uppercase tracking-wider font-semibold">
                Estrategia e Innovación &bull; ERP
            </div>
        </div>
    </div>
 
    {{-- Footer --}}
    <footer class="py-6 border-t border-slate-200/80 bg-white text-center text-xs text-slate-400">
        &copy; {{ date('Y') }} Estrategia e Innovación. Todos los derechos reservados.
    </footer>
 
</body>
</html>
