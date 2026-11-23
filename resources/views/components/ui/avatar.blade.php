@props(['src' => null, 'size' => null])

{{-- A person's photo from HR on HR's avatar red (the hr-avatar token, HR's --color-primary), or HR's default picture without one (or if the photo fails to load).
     Pass the circle's own classes; the photo fills whatever shape they draw.
     size: pixels. Locks a fixed round box inline (width, height, min/max, radius 999, object-fit cover) so the photo can never outgrow its row, whatever the page's CSS does to img (preflight's height:auto, a class missing from the build). --}}
<img src="{{ $src ?? asset(\Modules\Hr\Models\Employee::DefaultAvatar) }}" alt="" loading="lazy" decoding="async" onerror="this.onerror = null; this.src = '{{ asset(\Modules\Hr\Models\Employee::DefaultAvatar) }}'" @if ($size !== null) data-avatar-size="{{ (int) $size }}" style="width:{{ (int) $size }}px;height:{{ (int) $size }}px;min-width:{{ (int) $size }}px;min-height:{{ (int) $size }}px;max-width:none;max-height:{{ (int) $size }}px;border-radius:999px;object-fit:cover" @endif {{ $attributes->class(['!bg-hr-avatar object-cover', 'shrink-0 rounded-full' => $size !== null]) }}>
