<select {{
    $attributes->merge([
        'class' => 'w-full py-2 px-3 text-body text-neutral-900 placeholder-neutral-400 rounded-control border border-neutral-300 bg-white shadow-e1 focus:outline-none focus:ring-2 focus:ring-secondary-light focus:border-primary transition'
])
}}>
    {{$slot}}
</select>
