@props(['name'])@error($name)<p {{ $attributes->merge(['class' => 'mt-1 text-xs text-danger']) }} role="alert">{{ $message }}</p>@enderror
