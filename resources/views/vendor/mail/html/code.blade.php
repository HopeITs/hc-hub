{{--
    A one-time code (App\Notifications\EmailLoginCode), shown large in a
    monospaced face so it is easy to read and to copy exactly. Always left to
    right, even in Arabic mail. Inline styles: mail clients drop most CSS.
--}}
@props([
    'caption' => null,
])
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 24px 0;">
<tr>
<td align="center">
<p dir="ltr" style="margin: 0; font-family: Consolas, 'Courier New', monospace; font-size: 32px; font-weight: 700; letter-spacing: 8px; color: #414042;">{{ trim($slot) }}</p>
@if (filled($caption))
<p style="margin: 8px 0 0; font-size: 14px; color: #6D6E71;">{{ $caption }}</p>
@endif
</td>
</tr>
</table>
