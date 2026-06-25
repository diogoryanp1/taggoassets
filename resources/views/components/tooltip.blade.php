@props(['text'])<span {{ $attributes->merge(['class' => 'inline-flex items-center']) }} title="{{ $text }}">{{ $slot }}</span>
