@if($attributes->has('href'))
    <a {{
    $attributes->merge([
        'class' => 'cursor-pointer p-2 text-secondary-medium inline-flex items-center',
        'target' => '_blank',
        'title' => __('general.export'),
    ])
}}>
        <x-icons.export/>
    </a>
@else

    <button {{
    $attributes->merge([
        'class' => 'cursor-pointer text-secondary-medium p-2 inline-flex items-center',
        'title' => __('general.export'),
    ])
}}>
        <x-icons.export/>
    </button>
@endif
