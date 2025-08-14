<div class="relative w-full">
    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
        <x-icons.calendar/>
    </div>
    <input {{$attributes->merge([
    'class' => 'w-full py-2 px-8 text-sm text-gray-900 placeholder-gray-400 rounded-md border border-gray-300 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition',
    'type' => 'text',
    'placeholder' => 'اختر تاريخ'
])}}
           datepicker datepicker-autoselect-today datepicker-autohide datepicker-orientation="top right" datepicker-format="dd-mm-yyyy">
</div>
