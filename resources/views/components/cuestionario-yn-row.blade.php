@props(['label', 'id'])
<tr class="border-b border-slate-100 even:bg-slate-50 odd:bg-white">
    <td class="px-4 py-2.5 text-slate-700 font-medium">{{ $label }}</td>
    <td class="px-2 py-2 text-center w-16">
        <label class="inline-flex flex-col items-center gap-1 cursor-pointer">
            <input type="radio" name="{{ $id }}" value="1" class="w-4 h-4 text-indigo-600 border-slate-300 cursor-pointer">
            <span class="text-xs text-slate-500">Sí</span>
        </label>
    </td>
    <td class="px-2 py-2 text-center w-16">
        <label class="inline-flex flex-col items-center gap-1 cursor-pointer">
            <input type="radio" name="{{ $id }}" value="0" class="w-4 h-4 text-indigo-600 border-slate-300 cursor-pointer" checked>
            <span class="text-xs text-slate-500">No</span>
        </label>
    </td>
    <td class="px-4 py-2"></td>
</tr>
