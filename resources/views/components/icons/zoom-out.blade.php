@if(app()->getLocale() === 'en')

    <svg {{
    $attributes->merge([
        'class' => ' size-5' ]) }}
         aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7"/>
    </svg>
@else

    <svg {{
    $attributes->merge([
        'class' => ' size-5' ]) }}aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"/>
    </svg>


@endif
