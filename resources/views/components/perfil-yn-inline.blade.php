@props(['name', 'checked' => ''])
<div class="inline-flex items-center gap-4 flex-shrink-0">
    <label class="inline-flex items-center gap-1.5 cursor-pointer">
        <input type="checkbox" name="{{ $name }}" value="1" {{ $checked }}
               class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-400">
        <span class="text-sm font-medium text-slate-700">Sí</span>
    </label>
</div>
