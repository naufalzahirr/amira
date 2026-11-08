@props(['mentor', 'size' => null])
@php
    $icon = $mentor->icon ? config("amira_mentor.icons.{$mentor->icon}") : null;
@endphp
<div {{ $attributes->class(['avatar', "c-{$mentor->color}", "avatar-{$size}" => $size]) }} aria-hidden="true">
    @if ($icon)
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $icon[1] !!}</svg>
    @else
        {{ $mentor->initial() }}
    @endif
</div>
