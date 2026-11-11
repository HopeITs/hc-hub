@props(['src' => null])

{{-- A person's photo from HR on HR's avatar red (the hr-avatar token, HR's --color-primary), or HR's default picture without one (or if the photo fails to load).
     Pass the circle's own classes; the photo fills whatever shape they draw. --}}
<img src="{{ $src ?? asset(\Modules\Hr\Models\Employee::DefaultAvatar) }}" alt="" loading="lazy" decoding="async" onerror="this.onerror = null; this.src = '{{ asset(\Modules\Hr\Models\Employee::DefaultAvatar) }}'" {{ $attributes->class('!bg-hr-avatar object-cover') }}>
