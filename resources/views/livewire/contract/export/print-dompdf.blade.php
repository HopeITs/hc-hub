{{-- @php
    $dir        = ($locale ?? app()->getLocale()) === 'ar' ? 'rtl' : 'ltr';
    $headerSrc  = public_path('assets/img/HC_SYR_Header2.png');
    $HEADER_HEIGHT_MM = 24;
    $TOP_MARGIN_MM    = 32;
@endphp

    <!doctype html>
<html lang="ar">
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
            font-size:15px; line-height:1; color:#111; background:#fff;
            direction: ltr;
        }
        .page-header{ position: relative; top:3px; left:0; right:0; height: {{ $HEADER_HEIGHT_MM }}mm; }
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
        .contract-footer{
            position: absolute;
            bottom: 40px;
            right: 50px;
            color: gray;
            font-weight: bold;
            opacity: 70%;
        }
    </style>
    <title></title>
</head>
<body>



@for($i = 1; $i <= $totalPages; $i++)
    <div class="page-header">
        <img src="{{ $headerSrc }}" alt="Header">
    </div>
    <section class="pdf-page">
        @includeIf("livewire.contract.export.{$category}.pages.page{$i}", [
            'contract'    => $contract,
            'locale'      => $locale,
            'pageNo'      => $i,
            'totalPages'  => $totalPages,
            'forPdf'      => true,
        ])
        <div class="contract-footer">{{ $i }} of {{ $totalPages }}</div>
    </section>
@endfor

</body>
</html>  --}}








{{-- 
@php
    $dir        = ($locale ?? app()->getLocale()) === 'ar' ? 'rtl' : 'ltr';
    
    // إجبار DomPDF على قراءة الصور عبر تحويلها إلى Base64
    $headerPath = public_path('assets/img/Header.png');
    $headerSrc  = (file_exists($headerPath) && is_file($headerPath)) 
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($headerPath)) 
        : '';

    $HEADER_HEIGHT_MM = 24;
    $TOP_MARGIN_MM    = 38; 
@endphp
<!doctype html>
<html lang="ar">
<head>
    <meta charset="utf-8">
    <style>
        /* ==========================================
           FONTS (Matching _styles.blade.php)
        ========================================== */
        @font-face { font-family: 'somar-regular'; src: url('{{ public_path("fonts/SomarSans/SomarSans-Regular.ttf") }}') format('truetype'); font-weight:400; font-style:normal; }
        @font-face { font-family: 'somar-bold'; src: url('{{ public_path("fonts/SomarSans/SomarSans-Bold.ttf") }}') format('truetype'); font-weight: 700; font-style: normal; }
        @font-face { font-family: 'somar-black'; src: url('{{ public_path("fonts/SomarSans/SOMARSANS-BLACK.ttf") }}') format('truetype'); font-weight: 900; font-style: normal; }
        @font-face { font-family: 'NotoNaskhArabic'; src: url('{{ public_path("fonts/NotoNaskh/NotoNaskhArabic-Regular.ttf") }}') format('truetype'); font-weight: 400; font-style: normal; }
        @font-face { font-family: 'NotoNaskhArabic'; src: url('{{ public_path("fonts/NotoNaskh/NotoNaskhArabic-Bold.ttf") }}') format('truetype'); font-weight: 700; font-style: normal; }

        @page { 
            size: A4 portrait; 
            margin: {{ $TOP_MARGIN_MM }}mm 16mm 22mm 16mm; 
        }

        html, body {
            margin: 0; padding: 0;
            background: #fff;
            direction: ltr;
        }

        /* Fixed Header & Footer */
        .page-header { position: fixed; top: -38mm; left: -2px; right: 0; height: {{ $HEADER_HEIGHT_MM }}mm; }
        .page-header img { width: 100%; display: block; }
        
        .contract-footer {
            position: absolute;
            left: 0; right: 0; bottom: 12mm;
            text-align: center;
            font-family: 'somar-bold', sans-serif;
            font-weight: 700; font-size: 7pt;
            color: #414042; z-index: 2;
        }

        .pdf-page { page-break-after: always; position: relative; }
        .pdf-page:last-child { page-break-after: auto; }

        /* ==========================================
           TITLE
        ========================================== */
        .contract-title-center { width: 100%; text-align: center; margin-top: 4px; margin-bottom: 20px; }
        .contract-title-center .dual-row { display: table; width: 100%; margin: 0 auto; table-layout: fixed; }
        .contract-title-center .dual-en, 
        .contract-title-center .dual-ar, 
        .contract-title-separator {
            display: table-cell; vertical-align: middle;
            font-family: 'somar-bold', 'NotoNaskhArabic', sans-serif;
            font-weight: 700; font-size: 16pt; line-height: 1.2;
            color: #414042; text-align: center !important;
        }
        .contract-title-separator { width: 20px; }

        /* ==========================================
           BODY LAYOUT
        ========================================== */
        .dual-row { display: table; table-layout: fixed; width: 100%; margin-top: .35rem; }
        .dual-tight { margin-top: 0; }
        .dual-row > .dual-en, .dual-row > .dual-ar { display: table-cell; width: 50%; vertical-align: top; }
        .dual-col { line-height: 1.65; }

        /* ==========================================
           NORMAL TEXT (تم إصلاح اتجاه النص العربي هنا)
        ========================================== */
        .dual-en, .dual-en * {
            text-align: left !important; direction: ltr !important;
            font-family: 'somar-regular', sans-serif; font-weight: 400; font-size: 10.5pt;
            line-height: 1.5; color: #414042; unicode-bidi: embed !important;
        }

        .dual-ar, .dual-ar * {
            text-align: right !important; direction: rtl !important;
            font-family: 'somar-regular', 'NotoNaskhArabic', sans-serif; font-weight: 400; font-size: 11pt;
            line-height: 1.5; color: #414042; 
            /* unicode-bidi: plaintext !important; هذا ما يصلح انعكاس الكلمات */
            unicode-bidi: bidi-override !important;
        }

        /* ==========================================
           NORMAL BOLD
        ========================================== */
        .fw-bold .dual-en { font-family: 'somar-bold', sans-serif; font-weight: 700; font-size: 10.5pt; color: #414042; }
        .fw-bold .dual-ar { font-family: 'somar-bold', 'NotoNaskhArabic', sans-serif; font-weight: 700; font-size: 11pt; color: #414042; }

        /* ==========================================
           LABELS RED
        ========================================== */
        .h-label { color: #c2151c; font-family: 'somar-black', sans-serif; font-weight: 900; }
        .h-label .dual-en, .dual-en .h-label { font-size: 10.5pt; }
        .h-label .dual-ar, .dual-ar .h-label { font-size: 11pt; }

        .h-label-bold { color: #c2151c; font-family: 'somar-bold', sans-serif; font-weight: 700; }
        .h-label-bold .dual-en, .dual-en .h-label-bold { font-size: 10.5pt; }
        .h-label-bold .dual-ar, .dual-ar .h-label-bold { font-size: 11pt; }

        /* ==========================================
           BLACK TEXT
        ========================================== */
        strong { font-family: 'somar-bold', 'NotoNaskhArabic', sans-serif; font-weight: 900; color: #414042; }

        /* ==========================================
           SPACING
        ========================================== */
        .tight p { margin: 0 0 .45rem 0; }
        .avoid-break { page-break-inside: avoid; }
        .mt-1 { margin-top: 4px; } .mt-2 { margin-top: 8px; } .mt-3 { margin-top: 12px; } 
        .mt-4 { margin-top: 16px; } .mt-6 { margin-top: 24px; }
    </style>
    <title></title>
</head>
<body>

    <div class="page-header">
        @if($headerSrc) <img src="{{ $headerSrc }}" alt="Header"> @endif
    </div>

    @for($i = 1; $i <= $totalPages; $i++)
        <section class="pdf-page">
            @includeIf("livewire.contract.export.{$category}.pages.page{$i}", [
                'contract'    => $contract,
                'locale'      => $locale,
                'pageNo'      => $i,
                'totalPages'  => $totalPages,
                'forPdf'      => true,
            ])
            <div class="contract-footer">{{ $i }} / {{ $totalPages }}</div>
        </section>
    @endfor

</body>
</html> 



 --}}



























{{-- @php
    $dir        = ($locale ?? app()->getLocale()) === 'ar' ? 'rtl' : 'ltr';
    $headerSrc  = public_path('assets/img/Header.png');
    $HEADER_HEIGHT_MM = 24;
    $TOP_MARGIN_MM    = 32;
@endphp
<!doctype html>
<html lang="ar">
<head>
    <meta charset="utf-8">
    <style>
        /* FONTS - Using public_path for Dompdf */
        @font-face {
            font-family: 'somar';
            src: url('{{ public_path("fonts/SomarSans/SomarSans-Regular.ttf") }}') format('truetype');
            font-weight: 400; font-style: normal;
        }
        @font-face {
            font-family: 'somar';
            src: url('{{ public_path("fonts/SomarSans/SOMARSANS-BOLD.ttf") }}') format('truetype');
            font-weight: 700; font-style: normal;
        }
        @font-face {
            font-family: 'somar';
            src: url('{{ public_path("fonts/SomarSans/SOMARSANS-BLACK.ttf") }}') format('truetype');
            font-weight: 900; font-style: normal;
        }

        @page { size: A4; margin: {{ $TOP_MARGIN_MM }}mm 14mm 20mm 14mm; }

        html, body {
            margin: 0; padding: 0;
            font-family: 'somar', sans-serif;
            font-size: 11pt; line-height: 1.5; color: #414042; background: #fff;
            direction: ltr;
        }

        /* Header */
        .page-header { position: fixed; top: -25mm; left: -5mm; right: -5mm; height: 35mm; text-align: center; }
        .page-header img { width: 100%; }

        /* Page Layout */
        .pdf-page { page-break-after: always; position: relative; }
        .pdf-page:last-child { page-break-after: auto; }

        /* Mimic the Grid Layout using Tables */
        .dual-row { display: table; width: 100%; table-layout: fixed; margin-top: 5px; border-spacing: 15px 0; }
        .dual-en, .dual-ar { display: table-cell; width: 50%; vertical-align: top; }

        .dual-en { direction: ltr; text-align: left; font-size: 10.5pt; }
        .dual-ar { direction: rtl; text-align: right; font-size: 11pt; }

        /* Centered Title Styling */
        .contract-title-center { width: 100%; text-align: center; margin-bottom: 25px; }
        .title-table { margin: 0 auto; }
        .fw-title { font-weight: 700; font-size: 16pt; color: #414042; }
        .contract-title-separator { font-weight: 700; font-size: 16pt; padding: 0 10px; }

        /* Styles from your "Good" version */
        .h-label { color: #c2151c; font-weight: 900; }
        .h-label-bold { color: #c2151c; font-weight: 700; }
        .fw-bold { font-weight: 700; }
        strong { font-weight: 700; color: #414042; }
        .avoid-break { page-break-inside: avoid; }
        
        .mt-1 { margin-top: 4px; }
        .mt-2 { margin-top: 8px; }
        .mt-3 { margin-top: 12px; }
        .mt-4 { margin-top: 16px; }

        /* Footer / Page Number */
        .contract-footer {
            position: fixed; bottom: -10mm; left: 0; right: 0;
            text-align: center; font-size: 8pt; color: #414042; font-weight: 700;
        }
    </style>
</head>
<body>

@for($i = 1; $i <= $totalPages; $i++)
    <div class="page-header">
        <img src="{{ $headerSrc }}" alt="Header">
    </div>
    
    <section class="pdf-page">
        @includeIf("livewire.contract.export.{$category}.pages.page{$i}", [
            'contract'    => $contract,
            'locale'      => $locale,
            'pageNo'      => $i,
            'totalPages'  => $totalPages,
            'forPdf'      => true,
        ])
        <div class="contract-footer">{{ $i }} / {{ $totalPages }}</div>
    </section>
@endfor

</body>
</html> --}}











@php
    $dir        = ($locale ?? app()->getLocale()) === 'ar' ? 'rtl' : 'ltr';
    $headerSrc  = public_path('assets/img/Header.png');
    $HEADER_HEIGHT_MM = 24;
    $TOP_MARGIN_MM    = 32;
@endphp
<!doctype html>
<html lang="ar">
<head>
<meta charset="utf-8">
<style>
    /* ── FONTS (filesystem paths for Dompdf) ── */
    @font-face { font-family:'somar-regular'; src:url('{{ public_path("fonts/SomarSans/SomarSans-Regular.ttf") }}') format('truetype'); font-weight:400; font-style:normal; }
    @font-face { font-family:'somar-bold';    src:url('{{ public_path("fonts/SomarSans/SOMARSANS-BOLD.ttf") }}')    format('truetype'); font-weight:700; font-style:normal; }
    @font-face { font-family:'somar-black';   src:url('{{ public_path("fonts/SomarSans/SOMARSANS-BLACK.ttf") }}')   format('truetype'); font-weight:900; font-style:normal; }
    @font-face { font-family:'NotoNaskhArabic'; src:url('{{ public_path("fonts/NotoNaskh/NotoNaskhArabic-Regular.ttf") }}') format('truetype'); font-weight:400; font-style:normal; }
    @font-face { font-family:'NotoNaskhArabic'; src:url('{{ public_path("fonts/NotoNaskh/NotoNaskhArabic-Bold.ttf") }}')    format('truetype'); font-weight:700; font-style:normal; }

    /* ── PAGE ── */
    /* @page { size: A4; margin: {{ $TOP_MARGIN_MM }}mm 16mm 22mm 16mm; } */
@page { size: A4; margin: {{ $TOP_MARGIN_MM }}mm 16mm 28mm 16mm; }
    html, body {
        margin:0; padding:0;
        font-family:'somar-regular','NotoNaskhArabic','DejaVu Sans',sans-serif;
        font-size:12px; line-height:1.45;
        color:#414042; background:#fff;
        direction:ltr;
    }

    /* ── HEADER ── */
    /* .page-header { position:relative; top:0; left:0; right:0; height:{{ $HEADER_HEIGHT_MM }}mm; }
    .page-header img { position:absolute; top:-10px; left:-2px; right:0; width:100%; } */
.page-header {
    position: relative;
    top: 0;
    left: 0;
    right: 0;
    height: {{ $HEADER_HEIGHT_MM }}mm;
    padding: 4mm 6mm 0 6mm;   /* breathing room: top, left/right */
    box-sizing: border-box;
}
.page-header img {
    position: absolute;
    top: 4mm;                  /* pushes image down from edge */
    left: 6mm;
    right: 6mm;
    width: calc(100% - 12mm); /* respects the side padding */
}
    /* ── PAGE WRAPPER ── */
    .pdf-page { page-break-after:always; padding:0 50px; position:relative; }
    .pdf-page:last-child { page-break-after:auto; }

    /* ── DUAL COLUMN (table-based for Dompdf) ── */
    .dual-row   { display:table; table-layout:fixed; width:100%; margin-top:10px; }
    .dual-tight { margin-top:0; }
    .dual-row > .dual-en,
    .dual-row > .dual-ar { display:table-cell; width:50%; vertical-align:top; }

    /* ── ENGLISH COLUMN ── */
    .dual-en, .dual-en * {
        direction:ltr !important;
        unicode-bidi:embed !important;
        text-align:left !important;
        font-family:'somar-regular','NotoNaskhArabic','DejaVu Sans',sans-serif;
        font-size:10.5pt;
        line-height:1.5;
        color:#414042;
    }

    /* ── ARABIC COLUMN ── */
    .dual-ar, .dual-ar * {
        direction:rtl !important;
        unicode-bidi:bidi-override !important;
        text-align:right !important;
        font-family:'somar-regular','NotoNaskhArabic','DejaVu Sans',sans-serif;
        font-size:11pt;
        line-height:1.5;
        color:#414042;
    }

    /* ── TITLE ROW (centred, bold, large) ── */
    /* Dompdf can't flex, so we use a single-row table with auto-width cells */
    .contract-title-center { width:100%; text-align:center; margin-top:4px; margin-bottom:20px; }
    .contract-title-center .dual-row          { display:table; width:auto; margin:0 auto; }
    .contract-title-center .dual-en,
    .contract-title-center .dual-ar,
    .contract-title-center .dual-ar *,
    .contract-title-center .dual-en *,
    .contract-title-separator {
        display:table-cell;
        width:auto !important;
        white-space:nowrap;
        font-family:'somar-bold' !important;
        font-size:16pt !important;
        font-weight:700 !important;
        line-height:1.2 !important;
        color:#414042 !important;
        text-align:center !important;
        vertical-align:middle;
        padding:0 6px;
    }

    /* ── BOLD OVERRIDE (fw-bold wrapper) ── */
    .fw-bold .dual-en, .fw-bold .dual-en * { font-family:'somar-bold' !important; font-size:10.5pt; font-weight:700; color:#414042; }
    .fw-bold .dual-ar, .fw-bold .dual-ar * { font-family:'somar-bold' !important; font-size:11pt;   font-weight:700; color:#414042; }

    /* ── LABELS ── */
    .h-label          { color:#c2151c !important; font-family:'somar-black' !important; font-weight:900; }
    .h-label-bold     { color:#c2151c !important; font-family:'somar-bold'  !important; font-weight:700; }

    /* ── STRONG ── */
    strong { font-family:'somar-bold'; font-weight:700; color:#414042; }

    /* ── FOOTER ── */

      

    .contract-footer {
    position: absolute;
    left: 0;
    right: 0;
    text-align: center;
    bottom: -22mm;            /* sits in the bottom margin, centred */
    font-family: 'somar-bold';
    font-size: 7pt;
    font-weight: 700;
    color: #414042;
    padding: 0 6mm;           /* side breathing room matches header */
}

    /* ── SPACING ── */
    .mt-1{margin-top:4px;}  .mt-2{margin-top:8px;}
    .mt-3{margin-top:12px;} .mt-4{margin-top:16px;}
    .mt-6{margin-top:24px;} .mt-8{margin-top:32px;}
    .mt-10{margin-top:40px;}
    .avoid-break { page-break-inside:avoid; }
    ul { margin:0; padding:0 0 0 14px; } li { margin:2px 0; }
</style>
<title></title>
</head>
<body>

@for($i = 1; $i <= $totalPages; $i++)
    <div class="page-header">
        <img src="{{ $headerSrc }}" alt="Header">
    </div>
    <section class="pdf-page">
        @includeIf("livewire.contract.export.{$category}.pages.page{$i}", [
            'contract'   => $contract,
            'locale'     => $locale,
            'pageNo'     => $i,
            'totalPages' => $totalPages,
            'forPdf'     => true,
        ])
        <div class="contract-footer">{{ $i }} / {{ $totalPages }}</div>
    </section>
@endfor

</body>
</html>