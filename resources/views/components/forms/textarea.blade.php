<textarea
    {{ $attributes->merge([
    'class' => 'w-full py-2 px-3 text-sm text-gray-900 placeholder-gray-400 rounded-md border border-gray-300 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-secondary-light focus:border-primary transition',
    'rows' => '3'
]) }}
    >{{$slot}}
</textarea>
