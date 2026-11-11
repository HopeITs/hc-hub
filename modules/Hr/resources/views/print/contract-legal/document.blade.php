{{--
    The full legal text of a contract (hr.contracts.legal): every A4 page of
    the annual / trial / service template on the letterhead, English and
    Arabic side by side, for the browser's print-to-PDF. The old app rendered
    this with Browsershot, which the hub does not ship. $contract is the
    contract as signed (Contract::asSigned()): amendments are their own sheets.

    The clauses are the contract's own wording, printed in both languages at
    once whatever the page's locale, so they live in the page templates and
    not in lang files.
--}}
<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $fileTitle }}</title>
    @include('hr::print.contract-legal._styles')
    <style>
        html, body { margin: 0; padding: 0; background: #f4f4f5; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .pdf-page { padding: 8mm 0; }
        .contract-a4 { margin: 0 auto; }
        .print-bar { position: sticky; top: 0; z-index: 10; display: flex; gap: 8px; justify-content: center; padding: 10px 16px; background: rgba(255, 255, 255, .92); border-bottom: 1px solid #dcddde; font-family: 'somar-regular', sans-serif; }
        .print-bar button, .print-bar a { border-radius: 8px; padding: 6px 14px; font-size: 14px; border: 1px solid #be1f24; text-decoration: none; cursor: pointer; }
        .print-bar button { background: #be1f24; color: #fff; }
        .print-bar a { background: #fff; color: #be1f24; }
        .print-note { text-align: center; font-family: 'somar-regular', sans-serif; font-size: 13px; color: #58595b; margin: 8px 16px 0; }
        @media print {
            html, body { background: #fff; }
            .pdf-page { padding: 0; page-break-after: always; }
            .pdf-page:last-child { page-break-after: auto; }
            .contract-a4 { height: 297mm; box-shadow: none; border-radius: 0; }
        }
    </style>
</head>
<body>
    <div class="print-bar no-print">
        <button type="button" onclick="window.print()" data-test="print-legal-button">{{ __('hr_contracts.actions.print') }}</button>
        @if ($amendmentsUrl !== null)
            <a href="{{ $amendmentsUrl }}">{{ __('hr_contract_amendments.actions.list') }}</a>
        @endif
    </div>
    @if ($hasAmendments)
        <p class="print-note no-print">{{ __('hr_contract_amendments.as_signed') }}</p>
    @endif

    @for ($i = 1; $i <= $totalPages; $i++)
        <div class="pdf-page">
            <div class="contract-a4">
                <div class="contract-body">
                    @include("hr::print.contract-legal.{$category}.page{$i}", [
                        'contract' => $contract,
                        'leaveDays' => $leaveDays,
                        'pageNo' => $i,
                        'totalPages' => $totalPages,
                    ])
                </div>

                <div class="contract-footer">{{ $i }} / {{ $totalPages }}</div>
            </div>
        </div>
    @endfor
</body>
</html>
