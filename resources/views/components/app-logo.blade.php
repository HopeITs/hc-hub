{{-- Display size of assets/img/001_HC.png (stored at 3x, 828x222, for sharp rendering on HiDPI screens);
     reserves the box so the logo cannot shift layout.
     CSS classes passed by the caller still win over the width/height attributes. --}}
<img src="{{ asset('assets/img/001_HC.png') }}"
     alt="{{ config('app.name') }}"
     width="276"
     height="74"
     decoding="async"
     fetchpriority="high"
     {{$attributes->merge(['class' => 'app-logo'])}}
/>
