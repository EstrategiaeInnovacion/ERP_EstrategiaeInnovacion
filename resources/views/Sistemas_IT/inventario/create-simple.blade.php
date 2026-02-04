<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Test - Crear Inventario</title>
</head>
<body>
    <h1>Test: Crear Artículo de Inventario</h1>
    
    @if(session('success'))
        <div style="color: green; padding: 10px; border: 1px solid green; margin: 10px 0;">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div style="color: red; padding: 10px; border: 1px solid red; margin: 10px 0;">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div style="color: red; padding: 10px; border: 1px solid red; margin: 10px 0;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('inventario.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        
        <p>
            <label>Categoría *:</label><br>
            <select name="categoria" required>
                <option value="">Seleccionar...</option>
                @foreach($categorias as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </p>

        <p>
            <label>Artículo *:</label><br>
            <input type="text" name="articulo" required>
        </p>

        <p>
            <label>Modelo *:</label><br>
            <input type="text" name="modelo" required>
        </p>

        <p>
            <label>Cantidad *:</label><br>
            <input type="number" name="cantidad" value="1" min="1" required>
        </p>

        <p>
            <label>Estado *:</label><br>
            <select name="estado" required>
                <option value="">Seleccionar...</option>
                @foreach($estados as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </p>

        <p>
            <label>Observaciones:</label><br>
            <textarea name="observaciones"></textarea>
        </p>

        <p>
            <label>Imágenes:</label><br>
            <input type="file" name="imagenes[]" multiple accept="image/*">
        </p>

        <p>
            <button type="submit">Guardar Artículo</button>
            <a href="{{ route('inventario.index') }}">Cancelar</a>
        </p>
    </form>
</body>
</html>