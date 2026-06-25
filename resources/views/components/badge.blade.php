@props(['variant' => 'neutral'])
@php($variants = [
    'success' => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
    'warning' => 'bg-amber-50 text-amber-800 ring-amber-200',
    'danger' => 'bg-red-50 text-red-800 ring-red-200',
    'info' => 'bg-sky-50 text-sky-800 ring-sky-200',
    'purple' => 'bg-violet-50 text-violet-800 ring-violet-200',
    'neutral' => 'bg-slate-100 text-slate-700 ring-slate-200',
])
<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 '.($variants[$variant] ?? $variants['neutral'])]) }}>{{ $slot }}</span>
