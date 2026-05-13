@props(['label', 'name', 'checked' => ''])
<div class="flex items-center gap-4 py-2">
    <span class="text-sm text-slate-700 flex-1">{{ $label }}</span>
    <label class="inline-flex items-center gap-1.5 cursor-pointer">
        <input type="checkbox" name="{{ $name }}" value="1" {{ $checked }}
               class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-400">
        <span class="text-sm text-slate-600">Sí</span>
    </label>
</div>
