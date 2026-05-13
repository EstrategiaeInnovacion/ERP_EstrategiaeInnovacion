@props(['label', 'name', 'value' => ''])
<div class="{{ $attributes->get('class') }}">
    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1.5">{{ $label }}</label>
    <textarea name="{{ $name }}" rows="3"
              class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 transition resize-none bg-white">{{ $value }}</textarea>
</div>
