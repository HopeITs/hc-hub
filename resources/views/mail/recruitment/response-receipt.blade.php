<x-mail::message>
<div dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}" style="text-align: {{ $locale === 'ar' ? 'right' : 'left' }};">

# {{ __('recruitment-applicant.response_receipt.heading', [], $locale) }}

{{ __('recruitment-applicant.response_receipt.greeting', ['name' => $name], $locale) }}

{{ __('recruitment-applicant.response_receipt.intro', [], $locale) }}

**{{ __('recruitment-applicant.response_receipt.position', [], $locale) }}:** {{ $position }}

**{{ __('recruitment-applicant.response_receipt.submitted_at', [], $locale) }}:** {{ $submittedAt }}

---

## {{ __('recruitment-applicant.response_receipt.responses', [], $locale) }}

@foreach ($responses as $response)
**{{ $response['label'] }}**  
{{ $response['value'] }}

@endforeach

{{ __('recruitment-applicant.response_receipt.footer', [], $locale) }}

{{ __('recruitment-applicant.response_receipt.signature', [], $locale) }}

</div>
</x-mail::message>
