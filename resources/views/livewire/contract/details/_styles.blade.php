<style>
    .contract-a4{
        width: 210mm;
        height: 300mm;
        max-width: 100%;
        margin: 0 auto;
        background: #fff url('{{ asset('assets/img/header.svg') }}') no-repeat top center;
        background-size:95%;
        padding: 28mm 18mm 18mm;
        box-shadow: 0 8px 24px rgba(0,0,0,.06);
        border-radius: 16px;
        position: relative;
        direction: ltr;
        display: flex;
        flex-direction: column;
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
</style>
