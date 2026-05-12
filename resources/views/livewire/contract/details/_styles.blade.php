{{-- <style>
    .contract-a4{
        width: 210mm;
        height: 300mm;
        max-width: 100%;
        margin: 0 auto;
        background: #fff url('{{ asset('assets/img/Header.svg') }}') no-repeat top center;
        background-size:93%;
        padding: 35mm 10mm 10mm 10mm ;
        box-shadow: 0 8px 24px rgba(0,0,0,.06);
        background-position: center 8mm;
        border-radius: 16px;
        position: relative;
        direction: ltr;
        display: flex;
        flex-direction: column;
    }

        .contract-title-center{
        width: 100%;
        text-align: center;
        margin-top: 4px;
        margin-bottom: 20px;
    }

    .contract-title-center .dual-row{
        display: flex !important;
        justify-content: center !important;
        align-items: center !important;
        gap: 10px !important;
        grid-template-columns: unset !important;
    }

    .contract-title-center .dual-en,
    .contract-title-center .dual-ar{
        width: auto !important;
        flex: unset !important;
        font-family: 'somar','NotoNaskhArabic','DejaVu Sans',sans-serif !important;
        font-weight: 700 !important;
        font-size: 16pt !important;
        line-height: 1.2 !important;
        text-align: center !important;
    }

    .contract-title-separator{
        font-family: 'somar','NotoNaskhArabic','DejaVu Sans',sans-serif;
        font-weight: 700;
        font-size: 16pt;
    }
    
    .contract-body{
        margin-top: 2mm;

        flex: 1;
        overflow: hidden;
    }

    .contract-footer{
        position: absolute;
        right: 18mm;
        bottom: 10mm;
        font-size: 12px;
        opacity: .7;
    }

    .h-label { color:#d11f2f; font-weight:700; }
    .tight p{ margin:0 0 .45rem 0; }

    .dual-row{
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        align-items: start;
        margin-top: .35rem;
    }
    .dual-col{ line-height: 1.65; }
    .dual-en{ text-align: left;  direction: ltr; }
    .dual-ar{
        text-align: right; direction: rtl;
        unicode-bidi: plaintext;
    }

    @media print{
        @page { size: A4 portrait; margin: 0; }
        .no-print{ display:none!important; }
        .contract-a4{ box-shadow: none; border-radius: 0; }
        .page-break{ page-break-after: always; }
    }
</style> --}}




{{-- <style>
    /* =========================
       FONTS
    ========================== */
    @font-face{
        font-family:'somar';
        src:url('{{ asset('fonts/SomarSans/SomarSans-Regular.ttf') }}') format('truetype');
        font-weight:400;
    }

        @font-face {
            font-family: 'somar';
            src: url('{{ public_path('fonts/SomarSans/SomarSans-Bold.ttf') }}') format('truetype');
            font-weight: 700;
            font-style: normal;
        }
        @font-face {
            font-family: 'somar';
            src: url('{{ public_path('fonts/SomarSans/SomarSans-Black.ttf') }}') format('truetype');
            font-weight: 900;
        }
        @font-face {
            font-family: 'NotoNaskhArabic';
            src: url('{{ public_path('fonts/NotoNaskh/NotoNaskhArabic-Regular.ttf') }}') format('truetype');
            font-weight: 400;
            font-style: normal;
        }

        @font-face {
            font-family: 'NotoNaskhArabic';
            src: url('{{ public_path('fonts/NotoNaskh/NotoNaskhArabic-Bold.ttf') }}') format('truetype');
            font-weight: 700;
            font-style: normal;
        }

    :root{
        --green:#414042;
        --red:#d11f2f;
    }

    /* =========================
       PAGE
    ========================== */
    .contract-a4{
        width: 210mm;
        height: 300mm;
        max-width: 100%;
        margin: 0 auto;
        background: #fff url('{{ asset('assets/img/Header.svg') }}') no-repeat top center;
        background-size:93%;
        padding: 35mm 10mm 10mm 10mm ;
        box-shadow: 0 8px 24px rgba(0,0,0,.06);
        background-position: center 8mm;
        border-radius: 16px;
        position: relative;
        direction: ltr;
        display: flex;
        flex-direction: column;
    }

    .contract-body{
        margin-top:2mm;
    }

    .contract-footer{
        position:absolute;
        bottom:10mm;
        right:18mm;
        font-family:'Somar Sans Black';
        font-size:7pt;
        color:var(--green);
    }

    /* =========================
       TITLE
    ========================== */
    .contract-title-center{
        text-align:center;
        margin-bottom:20px;
    }

    .contract-title-center .dual-row{
        display:flex !important;
        justify-content:center;
        align-items:center;
        gap:10px;
    }

    .contract-title-center .dual-en,
    .contract-title-center .dual-ar{
        font-family:'Somar Sans';
        font-weight:700;
        font-size:16pt;
        color:var(--green);
        line-height:1.2;
    }

    .contract-title-separator{
        font-family:'Somar Sans';
        font-weight:700;
        font-size:16pt;
        color:var(--green);
    }

    /* =========================
       BODY GRID
    ========================== */
    .dual-row{
        display:grid;
        grid-template-columns:1fr 1fr;
        gap:2rem;
        margin-top:.35rem;
        align-items:start;
    }

    .dual-col{
        line-height:1.6;
    }

    .dual-en{
        direction:ltr;
        text-align:left;
        font-family:'Somar Sans';
        font-weight:400;
        font-size:10.5pt;
        color:var(--green);
        line-height:20pt;
    }

    .dual-ar{
        direction:rtl;
        text-align:right;
        unicode-bidi:plaintext;
        font-family:'Somar Sans';
        font-weight:400;
        font-size:11pt;
        color:var(--green);
        line-height:16pt;
    }
    .fw-title{
    font-size:16pt !important;
    font-weight:700 !important;
}

    /* =========================
       LABELS
    ========================== */
    .h-label{
        color:var(--red);
        font-family:'Somar Sans';
        font-weight:700;
    }

    strong{
        font-family:'Somar Sans Black';
        color:#2c2c2c;
        font-weight:900;
    }

    /* spacing */
    .mt-1{margin-top:4px;}
    .mt-2{margin-top:8px;}
    .mt-3{margin-top:12px;}
    .mt-4{margin-top:16px;}
    .mt-6{margin-top:24px;}

    .avoid-break{
        page-break-inside:avoid;
    }

    /* =========================
       PRINT
    ========================== */
    @media print{
        @page{
            size:A4 portrait;
            margin:0;
        }

        .contract-a4{
            box-shadow:none;
            border-radius:0;
        }

        .no-print{
            display:none !important;
        }
    }
</style>  --}}







{{-- WAS GOOD --}}
 <style>
    /* ==========================================
       FONTS
    ========================================== */
@font-face {
    font-family: 'somar-regular';
    src: url('{{ asset("fonts/SomarSans/SomarSans-Regular.ttf") }}') format('truetype');
     font-weight:400; font-style:normal;
}

@font-face {
    font-family: 'somar-bold';
    src: url('{{ asset("fonts/SomarSans/SOMARSANS-BOLD.ttf") }}') format('truetype');
                font-weight: 700;
            font-style: normal;
}

@font-face {
    font-family: 'somar-black';
    src: url('{{ asset("fonts/SomarSans/SOMARSANS-BLACK.ttf") }}') format('truetype');
                font-weight: 900;
            font-style: normal;
}
        @font-face {
            font-family: 'NotoNaskhArabic';
            src: url('{{ asset("fonts/NotoNaskh/NotoNaskhArabic-Regular.ttf") }}') format('truetype');
            font-weight: 400;
            font-style: normal;
        }

        @font-face {
            font-family: 'NotoNaskhArabic';
            src: url('{{ asset("fonts/NotoNaskh/NotoNaskhArabic-Bold.ttf") }}') format('truetype');
            font-weight: 700;
            font-style: normal;
        }

    :root{
        --main:#414042;
        --red:#c2151c;
    }

    /* ==========================================
       PAGE
    ========================================== */
    .contract-a4{
        width:210mm;
        height:300mm;
        max-width:100%;
        margin:0 auto;
        background:#fff url('{{ asset("assets/img/Header.svg") }}') no-repeat top center;
        background-size:93%;
        background-position:center 8mm;
        padding:35mm 10mm 10mm 10mm;
        box-shadow:0 8px 24px rgba(0,0,0,.06);
        border-radius:16px;
        position:relative;
        direction:ltr;
        display:flex;
        flex-direction:column;
    }

    .contract-body{
        margin-top:2mm;
        flex:1;
        overflow:hidden;
    }

    .contract-footer{
        position:absolute;
        right:18mm;
        bottom:10mm;
        font-family:'Somar Sans';
        font-weight:700;
        font-size:7pt;
        color:var(--main);
    }

    /* ==========================================
       TITLE
       Voluntary Contract | عقد تطوع
    ========================================== */
    .contract-title-center{
        width:100%;
        text-align:center;
        margin-top:4px;
        margin-bottom:20px;
    }

    .contract-title-center .dual-row{
        display:flex !important;
        justify-content:center !important;
        align-items:center !important;
        gap:10px !important;
        grid-template-columns:unset !important;
    }

    .contract-title-center .dual-en,
    .contract-title-center .dual-ar,
    .contract-title-separator{
        width:auto !important;
        flex:unset !important;
        font-family:'somar-bold';
        font-weight:700;
        font-size:16pt;
        line-height:1.2;
        color:var(--main);
        text-align:center !important;
    }

    /* ==========================================
       BODY LAYOUT
    ========================================== */
    .dual-row{
        display:grid;
        grid-template-columns:1fr 1fr;
        gap:2rem;
        align-items:start;
        margin-top:.35rem;
    }

    .dual-col{
        line-height:1.65;
    }

    /* ==========================================
       NORMAL TEXT
    ========================================== */
    .dual-en{
        text-align:left;
        direction:ltr;
        font-family:'somar-regular';
        font-weight:400;
        font-size:10.5pt;
        line-height:20pt;
        color:var(--main);
    }

    .dual-ar{
        text-align:right;
        direction:rtl;
        unicode-bidi:plaintext;
        font-family:'somar-regular';
        font-weight:400;
        font-size:11pt;
        line-height:16pt;
        color:var(--main);
    }

    /* ==========================================
       NORMAL BOLD
       Between / بين
    ========================================== */
    .fw-bold .dual-en{
        font-family:'somar-bold';
        font-weight:700;
        font-size:10.5pt;
        color:var(--main);
    }

    .fw-bold .dual-ar{
        font-family:'somar-bold';
        font-weight:700;
        font-size:11pt;
        color:var(--main);
    }

    /* ==========================================
       LABELS RED
       First Party / الفريق الأول
    ========================================== */
    .h-label{
        color:var(--red);
        font-family:'somar-black';
        font-weight:900;
    }

    .h-label .dual-en,
    .dual-en .h-label{
        font-size:10.5pt;
    }

    .h-label .dual-ar,
    .dual-ar .h-label{
        font-size:11pt;
    }

    /* ==========================================
       BLACK TEXT
       names, numbers, positions
    ========================================== */
    strong{
        font-family:'somar-bold';
        font-weight:900;
        color:var(--main);
    }

    /* ==========================================
       SPACING
    ========================================== */
    .tight p{
        margin:0 0 .45rem 0;
    }

    .mt-1{margin-top:4px;}
    .mt-2{margin-top:8px;}
    .mt-3{margin-top:12px;}
    .mt-4{margin-top:16px;}
    .mt-6{margin-top:24px;}

    .avoid-break{
        page-break-inside:avoid;
    }

    /* ==========================================
       PRINT
    ========================================== */
    @media print{
        @page{
            size:A4 portrait;
            margin:0;
        }

        .no-print{
            display:none !important;
        }

        .contract-a4{
            box-shadow:none;
            border-radius:0;
        }

        .page-break{
            page-break-after:always;
        }
    }
</style>

