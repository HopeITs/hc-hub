@props([
    'id' => 'btn'
])
<div class="flex items-center">
    <input {{$attributes->merge([
    'id' => $id,
    'type' => 'radio',
    'class' => 'size-4 text-red-600 bg-gray-100 border-gray-300 focus:ring-red-500  focus:ring-2 '
])}}
    >
    <label for="{{$id}}" class="ms-2 text-sm font-medium text-gray-900 ">{{$slot}}</label>
</div>
