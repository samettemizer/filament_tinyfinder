@if ($file->isImage())
    <img
        src="{{ $file->url }}"
        alt="{{ $file->name }}"
        style="max-width: 320px; max-height: 240px; object-fit: contain;"
    >
@else
    <a href="{{ $file->url }}" target="_blank" rel="noopener">
        {{ $file->name }}
    </a>
@endif
