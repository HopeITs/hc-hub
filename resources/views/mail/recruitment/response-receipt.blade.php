<x-mail::message>
@php
    $direction = $locale === 'ar' ? 'rtl' : 'ltr';
    $align = $locale === 'ar' ? 'right' : 'left';
@endphp

<div dir="{{ $direction }}" style="direction: {{ $direction }}; text-align: {{ $align }};">

<h1 style="direction: {{ $direction }}; text-align: {{ $align }};">{{ __('recruitment-applicant.response_receipt.heading', [], $locale) }}</h1>

<p style="direction: {{ $direction }}; text-align: {{ $align }};">{{ __('recruitment-applicant.response_receipt.greeting', ['name' => $name], $locale) }}</p>

<p style="direction: {{ $direction }}; text-align: {{ $align }};">{{ __('recruitment-applicant.response_receipt.intro', [], $locale) }}</p>

<p style="direction: {{ $direction }}; text-align: {{ $align }};"><strong>{{ __('recruitment-applicant.response_receipt.position', [], $locale) }}:</strong> {{ $position }}</p>

<p style="direction: {{ $direction }}; text-align: {{ $align }};"><strong>{{ __('recruitment-applicant.response_receipt.submitted_at', [], $locale) }}:</strong> {{ $submittedAt }}</p>

<hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 24px 0;">

<h2 style="direction: {{ $direction }}; text-align: {{ $align }};">{{ __('recruitment-applicant.response_receipt.responses', [], $locale) }}</h2>

@foreach ($responses as $response)
<p style="direction: {{ $direction }}; text-align: {{ $align }}; margin-bottom: 16px;"><strong>{{ $response['label'] }}</strong><br>{{ $response['value'] }}</p>

@endforeach

<p style="direction: {{ $direction }}; text-align: {{ $align }};">{{ __('recruitment-applicant.response_receipt.footer', [], $locale) }}</p>

<p style="direction: {{ $direction }}; text-align: {{ $align }};">{{ __('recruitment-applicant.response_receipt.signature', [], $locale) }}</p>

</div>
</x-mail::message>
