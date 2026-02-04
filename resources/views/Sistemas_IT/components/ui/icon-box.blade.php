@props(['name', 'boxClass' => 'h-11 w-11', 'iconClass' => 'h-6 w-6', 'gradient' => true])

@php
    $boxClasses = $boxClass . ' inline-flex items-center justify-center rounded-xl ' . ($gradient ? 'bg-gradient-to-br from-blue-600 to-blue-500' : 'bg-blue-600') . ' text-white shadow-md shadow-blue-500/30';
    $iconClasses = $iconClass . '';
@endphp

<span {{ $attributes->merge(['class' => $boxClasses]) }}>
    <x-ui.icon :name="$name" class="{{ $iconClasses }}" />
</span>
