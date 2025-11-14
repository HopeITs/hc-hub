<form {{$attributes->merge([
    'class' => 'space-y-4 p-4 border rounded-md bg-white'
])}}>
    {{$slot}}
</form>
