@php
    $dir        = ($locale ?? app()->getLocale()) === 'ar' ? 'rtl' : 'ltr';
    $headerSrc  = public_path('assets/img/HC_SYR_Header2.png');
    $HEADER_HEIGHT_MM = 24;
    $TOP_MARGIN_MM    = 32;
@endphp
    <!doctype html>
<html lang="{{ app()->getLocale() }}" dir="ltr">
<head>
    <meta charset="utf-8">
    <style>
        @font-face{ font-family:'somar'; src:url('{{ public_path('fonts/SomarSans/SomarSans-Regular.ttf') }}') format('truetype'); font-weight:400; font-style:normal; }
        @font-face{ font-family:'somar'; src:url('{{ public_path('fonts/SomarSans/SomarSans-Bold.ttf') }}') format('truetype'); font-weight:700; font-style:normal; }
        @font-face{ font-family:'NotoNaskhArabic'; src:url('{{ public_path('fonts/NotoNaskh/NotoNaskhArabic-Regular.ttf') }}') format('truetype'); font-weight:400; font-style:normal; }
        @font-face{ font-family:'NotoNaskhArabic'; src:url('{{ public_path('fonts/NotoNaskh/NotoNaskhArabic-Bold.ttf') }}') format('truetype'); font-weight:700; font-style:normal; }

        @page { size: A4; margin: {{ $TOP_MARGIN_MM }}mm 16mm 22mm 16mm; }

        html, body{
            margin:0; padding:0;
            font-family:'somar','NotoNaskhArabic','DejaVu Sans',sans-serif;
            font-size:12px; line-height:1.45; color:#111; background:#fff;
            direction: ltr;
        }
        .page-header{ position: relative; top:0; left:0; right:0; height: {{ $HEADER_HEIGHT_MM }}mm; }
        .page-header img{ position:absolute; top: -10px ; left:-2px; right:0; width:100%; }

        .pdf-page{ page-break-after:always; padding:0 50px; }
        .pdf-page:last-child{ page-break-after:auto; }

        .dual-row{ display:table; table-layout:fixed; width:100%; margin-top:10px; }
        .dual-tight{ margin-top:0; }
        .dual-row > .dual-en, .dual-row > .dual-ar{ display:table-cell; width:50%; vertical-align:top; }

        .dual-en, .dual-en *{
            direction:ltr !important;
            unicode-bidi: embed !important;
            text-align:left !important;
        }

        .dual-ar, .dual-ar *{
            direction:rtl !important;
            unicode-bidi: bidi-override !important;
            text-align:right !important;
            font-family:'somar','NotoNaskhArabic','DejaVu Sans',sans-serif;
        }

        .h-label{ color:#E31E24; font-weight:700; }
        .fw-bold{ font-weight:700; }
        .muted { color:#6B7280; }
        .avoid-break { page-break-inside: avoid; }

        .mt-1{ margin-top: 4px; }  .mt-2{ margin-top: 8px; }
        .mt-3{ margin-top:12px; }  .mt-4{ margin-top:16px; }
        .mt-6{ margin-top:24px; }  .mt-8{ margin-top:32px; }
        .mt-10{margin-top:40px; }
        ul{ margin:0; padding:0 0 0 14px; } li{ margin:2px 0; }
    </style>
    <title></title>
</head>
<body>

<div class="page-header">
    <img src="{{ $headerSrc }}" alt="Header">
</div>

@for($i = 1; $i <= $totalPages; $i++)
    <section class="pdf-page">
        @includeIf("livewire.contract.details.{$category}.pages.page{$i}", [
            'contract'    => $contract,
            'locale'      => $locale,
            'pageNo'      => $i,
            'totalPages'  => $totalPages,
            'forPdf'      => true,
        ])
    </section>
@endfor

</body>
</html>
