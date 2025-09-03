@php
    $dir = ($locale ?? app()->getLocale()) === 'ar' ? 'rtl' : 'ltr';
@endphp
    <!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: A4; margin: 0; }
        html, body { margin: 0; padding: 0; }
        .pdf-page{
            width:210mm; height:297mm;
            padding:16mm; box-sizing:border-box;
            page-break-after: always;
            background:#fff;
            font-family: somar, DejaVu Sans, sans-serif;
            line-height:1.35; font-size:12px;
        }
        .pdf-page:last-child{ page-break-after:auto; }
        .row { display: table; width: 100%; }
        .col { display: table-cell; vertical-align: top; }
        .col-6{ width: 50%; }
        .text-right{ text-align:right; } .text-left{ text-align:left; }
        .fw-bold{ font-weight:700; }
        .footer{
            position:absolute; left:0; right:0; bottom:8mm;
            text-align:center; font-size:11px;
        }
    </style>
    <title></title>
</head>
<body>
@for($i = 1; $i <= $totalPages; $i++)
    <section class="pdf-page">
        @includeIf("livewire.contract.details.{$category}.pages.page{$i}", [
            'contract'    => $contract,
            'locale'      => $locale,
            'pageNo'      => $i,
            'totalPages'  => $totalPages,
        ])
        <div class="footer">{{ $i }} of {{ $totalPages }}</div>
    </section>
@endfor
</body>
</html>
