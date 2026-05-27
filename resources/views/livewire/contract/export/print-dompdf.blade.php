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
</html> 
 --}}







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








{{-- 


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
         @font-face{ font-family:'somar-regular'; src:url('{{ public_path("fonts/SomarSans/SomarSans-Regular.ttf") }}') format('truetype'); font-weight:400; font-style:normal; }
        @font-face{ font-family:'somar-bold'; src:url('{{ public_path("fonts/SomarSans/SOMARSANS-BOLD.ttf") }}') format('truetype'); font-weight:700; font-style:normal; }
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
    /* strong { font-family:'somar-bold'; font-weight:700; color:#414042; } */
strong, .dual-en strong, .dual-ar strong { 
    font-family: 'somar-bold' !important; 
    font-weight: 700 !important; 
    color: #414042; 
}
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




 --}}

{{-- 
 @php
    $dir        = ($locale ?? app()->getLocale()) === 'ar' ? 'rtl' : 'ltr';
    $headerSrc  = public_path('assets/img/Header.png');
    $footerSrc  = public_path('assets/img/Footer.png'); /* Add this line */
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
    /* Reduced bottom margin to 18mm to give the content more vertical room */
    @page { size: A4; margin: {{ $TOP_MARGIN_MM }}mm 16mm 18mm 16mm; }
    
    html, body {
        margin:0; padding:0;
        font-family:'somar-regular','NotoNaskhArabic','DejaVu Sans',sans-serif;
        font-size:12px; 
        line-height:1.45;
        color:#414042; background:#fff;
        direction:ltr;
    }

    /* ── HEADER ── */
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
        font-size:10pt;          /* Slightly reduced from 10.5pt */
        line-height:1.35;        /* Tightened from 1.5 to save space */
        color:#414042;
    }

    /* ── ARABIC COLUMN ── */
    .dual-ar, .dual-ar * {
        direction:rtl !important;
        unicode-bidi:bidi-override !important;
        text-align:right !important;
        font-family:'somar-regular','NotoNaskhArabic','DejaVu Sans',sans-serif;
        font-size:10.5pt;        /* Slightly reduced from 11pt */
        line-height:1.35;        /* Tightened from 1.5 to save space */
        color:#414042;
    }

    /* ── TITLE ROW (centred, bold, large) ── */
    .contract-title-center { width:100%; text-align:center; margin-top:4px; margin-bottom:12px; }
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
        font-size:15pt !important; /* Reduced from 16pt */
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

    /* ── STRONG (Fix for NotoNaskh Override) ── */
    strong, .dual-en strong, .dual-ar strong { 
        font-family: 'somar-bold' !important; 
        font-weight: 700 !important; 
        color: #414042; 
    }

    /* ── FOOTER ── */
    .contract-footer {
        position: absolute;
        left: 0;
        right: 0;
        text-align: center;
        bottom: -12mm;            /* Sits perfectly within the new 18mm bottom margin */
        font-family: 'somar-bold';
        font-size: 8pt;           
        font-weight: 700;
        color: #9ca3af;           /* Muted grey color for standard footer appearance */
        padding: 0 6mm;           
    }

    /* ── FOOTER IMAGE ── */
    .page-footer-img {
        position: absolute;
        bottom: -18mm; /* Pushes it down into the bottom margin */
        left: 0;
        right: 0;
    }
    .page-footer-img img {
        width: 100%;
        display: block;
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
        
        <div class="page-footer-img">
            <img src="{{ $footerSrc }}" alt="Footer">
        </div>

        <div class="contract-footer">{{ $i }} / {{ $totalPages }}</div>
    </section>
@endfor

</body>
</html> --}}

{{-- @php
    $dir        = ($locale ?? app()->getLocale()) === 'ar' ? 'rtl' : 'ltr';
    $headerSrc  = public_path('assets/img/Header.png');
    
    /* ADDED FOOTER IMAGE PATH HERE */
    $footerSrc  = public_path('assets/img/Footer.png'); 
    
    $HEADER_HEIGHT_MM = 24;
    $TOP_MARGIN_MM    = 32;
@endphp
<!doctype html>
<html lang="ar">
<head>
<meta charset="utf-8">
<style>
    /* ── FONTS ── */
    @font-face { font-family:'somar-regular'; src:url('{{ public_path("fonts/SomarSans/SomarSans-Regular.ttf") }}') format('truetype'); font-weight:400; font-style:normal; }
    @font-face { font-family:'somar-bold';    src:url('{{ public_path("fonts/SomarSans/SOMARSANS-BOLD.ttf") }}')    format('truetype'); font-weight:700; font-style:normal; }
    @font-face { font-family:'somar-black';   src:url('{{ public_path("fonts/SomarSans/SOMARSANS-BLACK.ttf") }}')   format('truetype'); font-weight:900; font-style:normal; }
    @font-face { font-family:'NotoNaskhArabic'; src:url('{{ public_path("fonts/NotoNaskh/NotoNaskhArabic-Regular.ttf") }}') format('truetype'); font-weight:400; font-style:normal; }
    @font-face { font-family:'NotoNaskhArabic'; src:url('{{ public_path("fonts/NotoNaskh/NotoNaskhArabic-Bold.ttf") }}')    format('truetype'); font-weight:700; font-style:normal; }

    /* ── PAGE ── */
    @page { size: A4; margin: {{ $TOP_MARGIN_MM }}mm 16mm 18mm 16mm; }
    
    html, body {
        margin:0; padding:0;
        font-family:'somar-regular','NotoNaskhArabic','DejaVu Sans',sans-serif;
        font-size:12px; 
        line-height:1.45;
        color:#414042; background:#fff;
        direction:ltr;
    }

    /* ── HEADER ── */
    .page-header {
        position: relative;
        top: 0; left: 0; right: 0;
        height: {{ $HEADER_HEIGHT_MM }}mm;
        padding: 4mm 6mm 0 6mm;
        box-sizing: border-box;
    }
    .page-header img {
        position: absolute;
        top: 4mm;
        left: 6mm;
        right: 6mm;
        width: calc(100% - 12mm);
    }

    /* ── PAGE WRAPPER ── */
    /* CRITICAL FIX: Removed 'position: relative;' so footers stick to the paper edge, not the text */
    .pdf-page { page-break-after:always; padding:0 50px; }
    .pdf-page:last-child { page-break-after:auto; }

    /* ── DUAL COLUMN ── */
    .dual-row   { display:table; table-layout:fixed; width:100%; margin-top:10px; }
    .dual-tight { margin-top:0; }
    .dual-row > .dual-en,
    .dual-row > .dual-ar { display:table-cell; width:50%; vertical-align:top; }

    .dual-en, .dual-en * {
        direction:ltr !important;
        unicode-bidi:embed !important;
        text-align:left !important;
        font-family:'somar-regular','NotoNaskhArabic','DejaVu Sans',sans-serif;
        font-size:10pt;
        line-height:1.35;
        color:#414042;
    }

    .dual-ar, .dual-ar * {
        direction:rtl !important;
        unicode-bidi:bidi-override !important;
        text-align:right !important;
        font-family:'somar-regular','NotoNaskhArabic','DejaVu Sans',sans-serif;
        font-size:10.5pt;
        line-height:1.35;
        color:#414042;
    }

    /* ── TITLE ROW ── */
    .contract-title-center { width:100%; text-align:center; margin-top:4px; margin-bottom:12px; }
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
        font-size:15pt !important;
        font-weight:700 !important;
        line-height:1.2 !important;
        color:#414042 !important;
        text-align:center !important;
        vertical-align:middle;
        padding:0 6px;
    }

    /* ── BOLD OVERRIDE ── */
    .fw-bold .dual-en, .fw-bold .dual-en * { font-family:'somar-bold' !important; font-size:10.5pt; font-weight:700; color:#414042; }
    .fw-bold .dual-ar, .fw-bold .dual-ar * { font-family:'somar-bold' !important; font-size:11pt;   font-weight:700; color:#414042; }
    .h-label          { color:#c2151c !important; font-family:'somar-black' !important; font-weight:900; }
    .h-label-bold     { color:#c2151c !important; font-family:'somar-bold'  !important; font-weight:700; }

    strong, .dual-en strong, .dual-ar strong { 
        font-family: 'somar-bold' !important; 
        font-weight: 700 !important; 
        color: #414042; 
    }

    /* ── FOOTER IMAGE & TEXT (FIXED POSITIONS) ── */
    /* Because .pdf-page is no longer relative, 'absolute' anchors to the bottom of the A4 paper */
    .page-footer-img {
        position: absolute;
        bottom: -18mm; 
        left: 0;
        right: 0;
    }
    .page-footer-img img {
        width: 100%;
        display: block;
    }

    .contract-footer {
        position: absolute;
        bottom: -10mm; /* Sits nicely inside the margin above the edge */
        left: 0;
        right: 0;
        text-align: center;
        font-family: 'somar-bold';
        font-size: 8pt;           
        font-weight: 700;
        color: #9ca3af;
        padding: 0 6mm;           
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
        
        <div class="page-footer-img">
            <img src="{{ $footerSrc }}" alt="Footer">
        </div>

        <div class="contract-footer">{{ $i }} / {{ $totalPages }}</div>
    </section>
@endfor

</body>
</html> --}}
{{-- 
@php
    $dir              = ($locale ?? app()->getLocale()) === 'ar' ? 'rtl' : 'ltr';
    $headerSrc        = public_path('assets/img/Header.png');
    $footerSrc        = public_path('assets/img/Footer.png');
    $HEADER_HEIGHT_MM = 24;
    $FOOTER_HEIGHT_MM = 12;
    $TOP_MARGIN_MM    = 32;
    $BOTTOM_MARGIN_MM = 20;
@endphp
<!doctype html>
<html lang="ar">
<head>
<meta charset="utf-8">
<style>
    /* ── FONTS ── */
    @font-face { font-family:'somar-regular'; src:url('{{ public_path("fonts/SomarSans/SomarSans-Regular.ttf") }}') format('truetype'); font-weight:400; font-style:normal; }
    @font-face { font-family:'somar-bold';    src:url('{{ public_path("fonts/SomarSans/SOMARSANS-BOLD.ttf") }}')    format('truetype'); font-weight:700; font-style:normal; }
    @font-face { font-family:'somar-black';   src:url('{{ public_path("fonts/SomarSans/SOMARSANS-BLACK.ttf") }}')   format('truetype'); font-weight:900; font-style:normal; }
    @font-face { font-family:'NotoNaskhArabic'; src:url('{{ public_path("fonts/NotoNaskh/NotoNaskhArabic-Regular.ttf") }}') format('truetype'); font-weight:400; font-style:normal; }
    @font-face { font-family:'NotoNaskhArabic'; src:url('{{ public_path("fonts/NotoNaskh/NotoNaskhArabic-Bold.ttf") }}')    format('truetype'); font-weight:700; font-style:normal; }

    /* ── PAGE ── */
    @page {
        size: A4;
        margin: {{ $TOP_MARGIN_MM }}mm 16mm {{ $BOTTOM_MARGIN_MM }}mm 16mm;
    }

    html, body {
        margin: 0; padding: 0;
        font-family: 'somar-regular','NotoNaskhArabic','DejaVu Sans',sans-serif;
        font-size: 12px;
        line-height: 1.35;
        color: #414042; background: #fff;
        direction: ltr;
    }

    /* ── HEADER ── */
    .page-header {
        height: {{ $HEADER_HEIGHT_MM }}mm;
        padding: 15px 25px 0 25px; /* Matches footer left/right */
        box-sizing: border-box;
    }
    .page-header img { width: 100%; display: block; }

    /* ── PAGE WRAPPER ── */
    .pdf-page {
        page-break-after: always;
        padding: 0 50px;
        position: relative;
        height: 245mm; /* CRITICAL: Locks the container height so footer doesn't float */
        box-sizing: border-box;
    }
    .pdf-page:last-child { page-break-after: auto; }

    /* ── PAGE COUNTER ── */
    .contract-footer {
        position: absolute;
        bottom: 2mm; /* Small padding from footer image */
        left: 0; right: 0;
        text-align: center;
        font-family: 'somar-bold';
        font-size: 7pt;
        font-weight: 700;
        color: #414042;
        opacity: 0.85;
    }

    /* ── FOOTER IMAGE ── */
    .page-footer {
        position: absolute;
        bottom: -20mm; /* Fixed in the bottom margin */
        left: 0; right: 0;
        padding: 0 25px; /* Exact same left/right padding as header */
        height: {{ $FOOTER_HEIGHT_MM }}mm;
        box-sizing: border-box;
    }
    .page-footer img { width: 100%; display: block; }

    /* ── DUAL COLUMN ── */
    .dual-row   { display: table; table-layout: fixed; width: 100%; margin-top: 6px; }
    .dual-tight { margin-top: 0; }
    .dual-row > .dual-en,
    .dual-row > .dual-ar { display: table-cell; width: 50%; vertical-align: top; }

    /* ── ENGLISH COLUMN ── */
    .dual-en, .dual-en * {
        direction: ltr !important;
        unicode-bidi: embed !important;
        text-align: left !important;
        font-family: 'somar-regular','NotoNaskhArabic','DejaVu Sans',sans-serif;
        font-size: 10pt;
        line-height: 1.35;
        color: #414042;
    }

    /* ── ARABIC COLUMN (FIXED) ── */
    .dual-ar, .dual-ar * {
        direction: rtl !important;
        unicode-bidi: embed !important; /* REMOVED bidi-override WHICH BROKE THE TEXT */
        text-align: right !important;
        font-family: 'somar-regular','NotoNaskhArabic','DejaVu Sans',sans-serif;
        font-size: 10.5pt;
        line-height: 1.35;
        color: #414042;
    }

    /* ── TITLE ROW ── */
    .contract-title-center { width: 100%; text-align: center; margin-top: 4px; margin-bottom: 10px; }
    .contract-title-center .dual-row { display: table; width: auto; margin: 0 auto; }
    .contract-title-center .dual-en,
    .contract-title-center .dual-ar,
    .contract-title-center .dual-ar *,
    .contract-title-center .dual-en *,
    .contract-title-separator {
        display: table-cell;
        width: auto !important;
        white-space: nowrap;
        font-family: 'somar-bold' !important;
        font-size: 15pt !important;
        font-weight: 700 !important;
        line-height: 1.2 !important;
        color: #414042 !important;
        text-align: center !important;
        vertical-align: middle;
        padding: 0 6px;
    }

    /* ── BOLD OVERRIDE ── */
    .fw-bold .dual-en, .fw-bold .dual-en * { font-family:'somar-bold' !important; font-size:10pt;   font-weight:700; color:#414042; }
    .fw-bold .dual-ar, .fw-bold .dual-ar * { font-family:'somar-bold' !important; font-size:10.5pt; font-weight:700; color:#414042; }

    /* ── LABELS ── */
    .h-label      { color:#c2151c !important; font-family:'somar-black' !important; font-weight:900; }
    .h-label-bold { color:#c2151c !important; font-family:'somar-bold'  !important; font-weight:700; }

    /* ── STRONG ── */
    strong, .dual-en strong, .dual-ar strong { 
        font-family: 'somar-bold' !important; 
        font-weight: 700 !important; 
        color: #414042; 
    }

    /* ── SPACING ── */
    .mt-1{margin-top:3px;}   .mt-2{margin-top:6px;}
    .mt-3{margin-top:10px;}  .mt-4{margin-top:14px;}
    .mt-6{margin-top:20px;}  .mt-8{margin-top:28px;}
    .mt-10{margin-top:36px;}
    .avoid-break { page-break-inside: avoid; }
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

        <div class="page-footer">
            <img src="{{ $footerSrc }}" alt="Footer">
        </div>

    </section>

@endfor

</body>



</html>  --}}
{{-- @php
    $headerSrc        = public_path('assets/img/Header.png');
    $footerSrc        = public_path('assets/img/Footer.png');
    $HEADER_HEIGHT_MM = 24;
    $FOOTER_HEIGHT_MM = 12;
    $TOP_MARGIN_MM    = 35; // Adjusted to provide space for header
    $BOTTOM_MARGIN_MM = 25; // Adjusted to provide space for footer
@endphp
<!doctype html>
<html lang="ar">
<head>
<meta charset="utf-8">
<style>
    /* ── FONTS ── */
    @font-face { font-family:'somar-regular'; src:url('{{ public_path("fonts/SomarSans/SomarSans-Regular.ttf") }}') format('truetype'); font-weight:400; }
    @font-face { font-family:'somar-bold';    src:url('{{ public_path("fonts/SomarSans/SOMARSANS-BOLD.ttf") }}')    format('truetype'); font-weight:700; }
    @font-face { font-family:'somar-black';   src:url('{{ public_path("fonts/SomarSans/SOMARSANS-BLACK.ttf") }}')   format('truetype'); font-weight:900; }
    @font-face { font-family:'NotoNaskhArabic'; src:url('{{ public_path("fonts/NotoNaskh/NotoNaskhArabic-Regular.ttf") }}') format('truetype'); }

    /* ── PAGE LAYOUT ── */
    @page {
        size: A4;
        margin: {{ $TOP_MARGIN_MM }}mm 16mm {{ $BOTTOM_MARGIN_MM }}mm 16mm;
    }

    body {
        margin: 0; padding: 0;
        font-family: 'somar-regular','NotoNaskhArabic', sans-serif;
        font-size: 12px;
        line-height: 1.35;
        color: #414042;
    }

    /* ── FIXED HEADER & FOOTER ── */
    .header {
        position: fixed;
        top: -{{ $TOP_MARGIN_MM }}mm;
        left: 0; right: 0;
        height: {{ $HEADER_HEIGHT_MM }}mm;
        padding: 15px 25px 0 25px;
    }
    .header img { width: 100%; display: block; }

    .footer {
        position: fixed;
        bottom: -{{ $BOTTOM_MARGIN_MM }}mm;
        left: 0; right: 0;
        height: {{ $FOOTER_HEIGHT_MM }}mm;
        padding: 0 25px;
        text-align: center;
    }
    .footer img { width: 100%; display: block; }
    .contract-footer {
        position: absolute;
        bottom: 5mm; width: 100%;
        font-family: 'somar-bold'; font-size: 8pt; color: #9ca3af;
    }

    /* ── DUAL COLUMN ── */
    .dual-row { display: table; table-layout: fixed; width: 100%; margin-top: 6px; }
    .dual-row > div { display: table-cell; width: 50%; vertical-align: top; }
    
    .dual-en, .dual-en * { direction: ltr !important; text-align: left !important; font-size: 10pt; }
    .dual-ar, .dual-ar * { direction: rtl !important; text-align: right !important; font-size: 10.5pt; }

    /* ── TYPOGRAPHY ── */
    .contract-title-center { width: 100%; text-align: center; margin: 4px 0 10px 0; }
    .contract-title-center .dual-row { width: auto; margin: 0 auto; }
    .contract-title-center .dual-en, .contract-title-center .dual-ar { width: auto !important; padding: 0 6px; font-family: 'somar-bold' !important; font-size: 15pt !important; }
    
    .fw-bold, .fw-bold * { font-family: 'somar-bold' !important; font-weight: 700; }
    .h-label { color: #c2151c !important; font-family: 'somar-black' !important; }
    strong { font-family: 'somar-bold' !important; font-weight: 700; }

    /* ── UTILITIES ── */
    .page-break { page-break-after: always; }
    .mt-1{margin-top:3px;} .mt-2{margin-top:6px;} .mt-3{margin-top:10px;}
    ul { margin:0; padding:0 0 0 14px; } li { margin:2px 0; }
</style>
</head>
<body>

@for($i = 1; $i <= $totalPages; $i++)
    <div class="header">
        <img src="{{ $headerSrc }}" alt="Header">
    </div>

    <div class="footer">
        <img src="{{ $footerSrc }}" alt="Footer">
        <div class="contract-footer">{{ $i }} / {{ $totalPages }}</div>
    </div>

    <div class="{{ $i < $totalPages ? 'page-break' : '' }}">
        @includeIf("livewire.contract.export.{$category}.pages.page{$i}", [
            'contract'   => $contract,
            'locale'     => $locale,
            'pageNo'     => $i,
            'totalPages' => $totalPages,
            'forPdf'     => true,
        ])
    </div>
@endfor

</body>
</html> --}}




{{-- PREMODIFIED_WORK(FOOTER IS have some padding , Header is GOOD,text is good  ) --}}
{{-- @php
    $dir              = ($locale ?? app()->getLocale()) === 'ar' ? 'rtl' : 'ltr';
    $headerSrc        = public_path('assets/img/Header.png');
    $footerSrc        = public_path('assets/img/Footer.png');
    $HEADER_HEIGHT_MM = 24;
    $FOOTER_HEIGHT_MM = 12;
@endphp
<!doctype html>
<html lang="ar">
<head>
<meta charset="utf-8">
<style>
    /* ── FONTS ── */
    @font-face { font-family:'somar-regular'; src:url('{{ public_path("fonts/SomarSans/SomarSans-Regular.ttf") }}') format('truetype'); font-weight:400; font-style:normal; }
    @font-face { font-family:'somar-bold';    src:url('{{ public_path("fonts/SomarSans/SOMARSANS-BOLD.ttf") }}')    format('truetype'); font-weight:700; font-style:normal; }
    @font-face { font-family:'somar-black';   src:url('{{ public_path("fonts/SomarSans/SOMARSANS-BLACK.ttf") }}')   format('truetype'); font-weight:900; font-style:normal; }
    @font-face { font-family:'NotoNaskhArabic'; src:url('{{ public_path("fonts/NotoNaskh/NotoNaskhArabic-Regular.ttf") }}') format('truetype'); font-weight:400; font-style:normal; }
    @font-face { font-family:'NotoNaskhArabic'; src:url('{{ public_path("fonts/NotoNaskh/NotoNaskhArabic-Bold.ttf") }}')    format('truetype'); font-weight:700; font-style:normal; }

    /*
     * ── PAGE MATH ──
     * A4 = 297mm tall
     * @page margin top : 8mm  (small gap above header)
     * @page margin bot : 2mm  (footer bleeds to near-bottom)
     * Content area     : 297 - 8 - 2 = 287mm
     * Header height    : 24mm
     * Section height   : 287 - 24 = 263mm  ← exact fit, no overflow
     */
    @page {
        size: A4;
        margin: 8mm 16mm 2mm 16mm;
    }

    html, body {
        margin: 0; padding: 0;
        font-family: 'somar-regular','NotoNaskhArabic','DejaVu Sans',sans-serif;
        font-size: 12px;
        line-height: 1.35;
        color: #414042; background: #fff;
        direction: ltr;
    }

    /* ── HEADER (in loop, normal flow) ── */
    .page-header {
        height: {{ $HEADER_HEIGHT_MM }}mm;   /* 24mm */
        padding: 4px 25px 0 25px;
        box-sizing: border-box;
    }
    .page-header img { width: 100%; display: block; }

    /* ── PAGE WRAPPER ──
       263mm = 287mm content area − 24mm header.
       Locked height means footer can never drift to the next page.
    ── */
    .pdf-page {
        page-break-after: always;
        padding: 4mm 50px 0 50px;   /* 4mm top gap between header and text */
        position: relative;
        height: 263mm;
        box-sizing: border-box;
    }
    .pdf-page:last-child { page-break-after: auto; }

    /* ── PAGE COUNTER ──
       Sits just above the footer image.
       bottom = 2mm (footer bleed) + 12mm (footer height) + 1mm gap = 15mm
    ── */
    .contract-footer {
        position: absolute;
        bottom: 15mm;
        left: 0; right: 0;
        text-align: center;
        font-family: 'somar-bold';
        font-size: 7pt;
        font-weight: 700;
        color: #414042;
        opacity: 0.85;
    }

    /* ── FOOTER IMAGE ──
       bottom: -2mm bleeds it into the 2mm @page bottom margin
       so the image sits flush near the physical page bottom.
    ── */
    .page-footer {
        position: absolute;
        bottom: -2mm;
        left: 0; right: 0;
        height: {{ $FOOTER_HEIGHT_MM }}mm;   /* 12mm */
        padding: 0 25px;
        box-sizing: border-box;
    }
    .page-footer img { width: 100%; display: block; }

    /* ── DUAL COLUMN ── */
    .dual-row   { display: table; table-layout: fixed; width: 100%; margin-top: 6px; }
    .dual-tight { margin-top: 0; }
    .dual-row > .dual-en,
    .dual-row > .dual-ar { display: table-cell; width: 50%; vertical-align: top; }

    /* ── ENGLISH COLUMN ── */
    .dual-en, .dual-en * {
        direction: ltr !important;
        unicode-bidi: embed !important;
        text-align: left !important;
        font-family: 'somar-regular','NotoNaskhArabic','DejaVu Sans',sans-serif;
        font-size: 10pt;
        line-height: 1.35;
        color: #414042;
    }

    /* ── ARABIC COLUMN ── */
    .dual-ar, .dual-ar * {
        direction: rtl !important;
        unicode-bidi: embed !important;
        text-align: right !important;
        font-family: 'somar-regular','NotoNaskhArabic','DejaVu Sans',sans-serif;
        font-size: 10.5pt;
        line-height: 1.35;
        color: #414042;
    }

    /* ── TITLE ROW ── */
    .contract-title-center { width: 100%; text-align: center; margin-top: 4px; margin-bottom: 10px; }
    .contract-title-center .dual-row { display: table; width: auto; margin: 0 auto; }
    .contract-title-center .dual-en,
    .contract-title-center .dual-ar,
    .contract-title-center .dual-ar *,
    .contract-title-center .dual-en *,
    .contract-title-separator {
        display: table-cell;
        width: auto !important;
        white-space: nowrap;
        font-family: 'somar-bold' !important;
        font-size: 15pt !important;
        font-weight: 700 !important;
        line-height: 1.2 !important;
        color: #414042 !important;
        text-align: center !important;
        vertical-align: middle;
        padding: 0 6px;
    }

    /* ── BOLD OVERRIDE ── */
    .fw-bold .dual-en, .fw-bold .dual-en * { font-family:'somar-bold' !important; font-size:10pt;   font-weight:700; color:#414042; }
    .fw-bold .dual-ar, .fw-bold .dual-ar * { font-family:'somar-bold' !important; font-size:10.5pt; font-weight:700; color:#414042; }

    /* ── LABELS ── */
    .h-label      { color:#c2151c !important; font-family:'somar-black' !important; font-weight:900; }
    .h-label-bold { color:#c2151c !important; font-family:'somar-bold'  !important; font-weight:700; }

    /* ── STRONG ── */
    strong, .dual-en strong, .dual-ar strong {
        font-family: 'somar-bold' !important;
        font-weight: 700 !important;
        color: #414042;
    }

    /* ── SPACING ── */
    .mt-1{margin-top:3px;}   .mt-2{margin-top:6px;}
    .mt-3{margin-top:10px;}  .mt-4{margin-top:14px;}
    .mt-6{margin-top:20px;}  .mt-8{margin-top:28px;}
    .mt-10{margin-top:36px;}
    .avoid-break { page-break-inside: avoid; }
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

        <div class="page-footer">
            <img src="{{ $footerSrc }}" alt="Footer">
        </div>

    </section>

@endfor

</body>
</html> --}}



{{-- MODIFIED_WORK(FOOTER IS IN Bottom of page , Header IS GOOD ) --}}

@php
    $dir              = ($locale ?? app()->getLocale()) === 'ar' ? 'rtl' : 'ltr';
    $headerSrc        = public_path('assets/img/Header.png');
    $footerSrc        = public_path('assets/img/Footer.png');
    $HEADER_HEIGHT_MM = 16;
    $FOOTER_HEIGHT_MM = 12;
@endphp
<!doctype html>
<html lang="ar">
<head>
<meta charset="utf-8">
<style>
    /* ── FONTS ── */
    @font-face { font-family:'somar-regular'; src:url('{{ public_path("fonts/SomarSans/SomarSans-Regular.ttf") }}') format('truetype'); font-weight:400; font-style:normal; }
    @font-face { font-family:'somar-bold';    src:url('{{ public_path("fonts/SomarSans/SOMARSANS-BOLD.ttf") }}')    format('truetype'); font-weight:700; font-style:normal; }
    @font-face { font-family:'somar-black';   src:url('{{ public_path("fonts/SomarSans/SOMARSANS-BLACK.ttf") }}')   format('truetype'); font-weight:900; font-style:normal; }
    @font-face { font-family:'NotoNaskhArabic'; src:url('{{ public_path("fonts/NotoNaskh/NotoNaskhArabic-Regular.ttf") }}') format('truetype'); font-weight:400; font-style:normal; }
    @font-face { font-family:'NotoNaskhArabic'; src:url('{{ public_path("fonts/NotoNaskh/NotoNaskhArabic-Bold.ttf") }}')    format('truetype'); font-weight:700; font-style:normal; }

    /*
     * ── PAGE MATH ──
     * A4 = 297mm tall
     * @page margin top : 8mm  (small gap above header)
     * @page margin bot : 2mm  (footer bleeds to near-bottom)
     * Content area     : 297 - 8 - 2 = 287mm
     * Header height    : 24mm
     * Section height   : 287 - 24 = 263mm  ← exact fit, no overflow
     */
    @page {
        size: A4;
        margin: 8mm 16mm 2mm 16mm;
    }

    html, body {
        margin: 0; padding: 0;
        font-family: 'somar-regular','NotoNaskhArabic','DejaVu Sans',sans-serif;
        font-size: 12px;
        line-height: 1.35;
        color: #414042; background: #fff;
        direction: ltr;
    }

    /* ── HEADER (in loop, normal flow) ── */
    .page-header {
        height: {{ $HEADER_HEIGHT_MM }}mm;   /* 24mm */
        padding: 6px 25px 0 25px;
        box-sizing: border-box;
    }
    .page-header img { width: 100%; display: block; }

    /* ── PAGE WRAPPER ──
       263mm = 287mm content area − 24mm header.
       Locked height means footer can never drift to the next page.
    ── */
    .pdf-page {
        page-break-after: always;
        padding: 4mm 50px 0 50px;   /* 4mm top gap between header and text */
        position: relative;
        height: 271mm;
        box-sizing: border-box;
    }
    .pdf-page:last-child { page-break-after: auto; }

    /* ── PAGE COUNTER ──
       Sits just above the footer image.
       bottom = 2mm (footer bleed) + 12mm (footer height) + 1mm gap = 15mm
    ── */
    .contract-footer {
        position: absolute;
        bottom: 4mm;
        left: 0; right: 0;
        text-align: center;
        font-family: 'somar-bold';
        font-size: 7pt;
        font-weight: 700;
        color: #414042;
    }

    /* ── FOOTER IMAGE ──
       bottom: -2mm bleeds it into the 2mm @page bottom margin
       so the image sits flush near the physical page bottom.
    ── */
    .page-footer {
        position: absolute;
        bottom: -12mm;
        left: 0; right: 0;
        height: {{ $FOOTER_HEIGHT_MM }}mm;   /* 12mm */
        padding: 0 25px;
        box-sizing: border-box;
    }
    .page-footer img { width: 100%; display: block; }

    /* ── DUAL COLUMN ── */
    .dual-row   { display: table; table-layout: fixed; width: 100%; margin-top: 6px; }
    .dual-tight { margin-top: 0; }
    .dual-row > .dual-en,
    .dual-row > .dual-ar { display: table-cell; width: 50%; vertical-align: top; }

    /* ── ENGLISH COLUMN ── */
    .dual-en, .dual-en * {
        direction: ltr !important;
        unicode-bidi: embed !important;
        text-align: left !important;
        font-family: 'somar-regular','NotoNaskhArabic','DejaVu Sans',sans-serif;
        font-size: 10pt;
        line-height: 1.35;
        color: #414042;
    }

    /* ── ARABIC COLUMN ── */
    .dual-ar, .dual-ar * {
        direction: rtl !important;
        unicode-bidi: embed !important;
        text-align: right !important;
        font-family: 'somar-regular','NotoNaskhArabic','DejaVu Sans',sans-serif;
        font-size: 10.5pt;
        line-height: 1.35;
        color: #414042;
    }

    /* ── TITLE ROW ── */
    .contract-title-center { width: 100%; text-align: center; margin-top: 4px; margin-bottom: 10px; }
    .contract-title-center .dual-row { display: table; width: auto; margin: 0 auto; }
    .contract-title-center .dual-en,
    .contract-title-center .dual-ar,
    .contract-title-center .dual-ar *,
    .contract-title-center .dual-en *,
    .contract-title-separator {
        display: table-cell;
        width: auto !important;
        white-space: nowrap;
        font-family: 'somar-bold' !important;
        font-size: 15pt !important;
        font-weight: 700 !important;
        line-height: 1.2 !important;
        color: #414042 !important;
        text-align: center !important;
        vertical-align: middle;
        padding: 0 6px;
    }

    /* ── BOLD OVERRIDE ── */
    .fw-bold .dual-en, .fw-bold .dual-en * { font-family:'somar-bold' !important; font-size:10pt;   font-weight:700; color:#414042; }
    .fw-bold .dual-ar, .fw-bold .dual-ar * { font-family:'somar-bold' !important; font-size:10.5pt; font-weight:700; color:#414042; }

    /* ── LABELS ── */
    .h-label      { color:#c2151c !important; font-family:'somar-black' !important; font-weight:900; }
    .h-label-bold { color:#c2151c !important; font-family:'somar-bold'  !important; font-weight:700; }

    /* ── STRONG ── */
    strong, .dual-en strong, .dual-ar strong {
        font-family: 'somar-bold' !important;
        font-weight: 700 !important;
        color: #414042;
    }

    /* ── SPACING ── */
    .mt-1{margin-top:3px;}   .mt-2{margin-top:6px;}
    .mt-3{margin-top:10px;}  .mt-4{margin-top:14px;}
    .mt-6{margin-top:20px;}  .mt-8{margin-top:28px;}
    .mt-10{margin-top:36px;}
    .avoid-break { page-break-inside: avoid; }
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

        <div class="page-footer">
            <img src="{{ $footerSrc }}" alt="Footer">
        </div>

    </section>

@endfor

</body>
</html>