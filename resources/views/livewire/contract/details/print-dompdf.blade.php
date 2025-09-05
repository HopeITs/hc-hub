@php
    $dir = ($locale ?? app()->getLocale()) === 'ar' ? 'rtl' : 'ltr';
@endphp
    <!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <style>
        @font-face{
            font-family:'somar';
            src:url('{{ public_path('fonts/SomarSans/SomarSans-Regular.otf') }}') format('opentype');
            font-weight:400; font-style:normal;
        }
        @font-face{
            font-family:'somar';
            src:url('{{ public_path('fonts/SomarSans/SOMARSANS-BOLD.OTF') }}') format('opentype');
            font-weight:700; font-style:normal;
        }
        @font-face{
            font-family:'NotoNaskhArabic';
            src:url('{{ public_path('fonts/NotoNaskh/NotoNaskhArabic-Regular.ttf') }}') format('truetype');
            font-weight:400; font-style:normal;
        }
        @font-face{
            font-family:'NotoNaskhArabic';
            src:url('{{ public_path('fonts/NotoNaskh/NotoNaskhArabic-Bold.ttf') }}') format('truetype');
            font-weight:700; font-style:normal;
        }
        @page { size: A4; margin: 24mm 16mm 22mm 16mm; }

        html, body{
            margin:0; padding:0;
            /* Arabic-capable base to avoid fallbacks in mixed runs */
            font-family:'NotoNaskhArabic','DejaVu Sans',sans-serif;
            font-size:12px; line-height:1.45; color:#111; background:#fff;
            direction:{{ $dir }};
        }
        .footer{
            position:fixed; left:0; right:0; bottom:8mm;
            text-align:center; font-size:11px; color:#6B7280;
        }
        .pagenum:before{ content: counter(page); }
        .pagecount:before{ content: counter(pages); }

        .pdf-page{ page-break-after:always; }
        .pdf-page:last-child{ page-break-after:auto; }

        /* Dual rows: two equal columns */
        .dual-row{ display:table; table-layout:fixed; width:100%; margin-top:10px; }
        .dual-tight{ margin-top:0; }
        .dual-row > .dual-en,
        .dual-row > .dual-ar{ display:table-cell; width:50%; vertical-align:top; }

        /* EN column → Somar */
        .dual-en{
            direction:ltr; text-align:left; padding-right:9mm;
            font-family:somar,'DejaVu Sans',sans-serif;
        }

        /* AR column → Force Naskh (guarantees shaping) */
        .dual-ar{
            direction:rtl; unicode-bidi:bidi-override; text-align:right; padding-left:9mm;
            font-family:'NotoNaskhArabic','DejaVu Sans',sans-serif !important;
        }

        /* Helpers */
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

<div class="footer">
    <span class="pagenum"></span> / <span class="pagecount"></span>
</div>

@for($i = 1; $i <= $totalPages; $i++)
    <section class="pdf-page">
        @includeIf("livewire.contract.details.{$category}.pages.page{$i}", [
          'contract'   => $contract,
          'locale'     => $locale,
          'pageNo'     => $i,
          'totalPages' => $totalPages,
        ])
    </section>
@endfor

</body>
</html>
