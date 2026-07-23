<form {{$attributes->merge([
    'class' => 'space-y-4 p-4 border rounded-control bg-white'
])}}>
    {{$slot}}
</form>
