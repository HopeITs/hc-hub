<div id="application-form-preview-banner" dir="ltr" {{ $attributes->class(['relative aspect-[4/1] overflow-hidden rounded-[16px] border border-secondary-light bg-primary shadow-card']) }}>
    <img src="{{ route('hr.recruitment.assets', ['file' => 'logo.svg']) }}" alt="{{ __('common.organisation_name') }}" class="absolute start-[9.5%] top-1/2 z-10 w-[21%] -translate-y-[55%] object-contain">
    <img src="{{ route('hr.recruitment.assets', ['file' => 'hiring.svg']) }}" alt="{{ __('common.we_are_hiring') }}" class="absolute end-[11%] top-[32%] z-10 w-[14.5%] object-contain">

    {{-- The banner is one fixed picture: the container is pinned dir="ltr", so these start-[…] offsets never mirror in Arabic. --}}
    <img src="{{ route('hr.recruitment.assets', ['file' => 'arrow2.svg']) }}" alt="" class="pointer-events-none absolute start-[87%] top-[1%] w-[1.2%] opacity-35" aria-hidden="true">
    <img src="{{ route('hr.recruitment.assets', ['file' => 'arrow2.svg']) }}" alt="" class="pointer-events-none absolute start-[91.1%] -top-[1%] w-[1.3%] opacity-55" aria-hidden="true">
    <img src="{{ route('hr.recruitment.assets', ['file' => 'arrow2.svg']) }}" alt="" class="pointer-events-none absolute start-[96.2%] -top-[5%] w-[2.3%] opacity-65" aria-hidden="true">
    <img src="{{ route('hr.recruitment.assets', ['file' => 'arrow2.svg']) }}" alt="" class="pointer-events-none absolute start-[78%] top-[21%] w-[1.3%] opacity-15" aria-hidden="true">
    <img src="{{ route('hr.recruitment.assets', ['file' => 'arrow2.svg']) }}" alt="" class="pointer-events-none absolute start-[83.3%] top-[14%] w-[2.5%] opacity-45" aria-hidden="true">
    <img src="{{ route('hr.recruitment.assets', ['file' => 'arrow2.svg']) }}" alt="" class="pointer-events-none absolute start-[89.2%] top-[17%] w-[3.8%]" aria-hidden="true">
    <img src="{{ route('hr.recruitment.assets', ['file' => 'arrow2.svg']) }}" alt="" class="pointer-events-none absolute start-[96.2%] top-[19%] w-[1.6%] opacity-55" aria-hidden="true">
    <img src="{{ route('hr.recruitment.assets', ['file' => 'arrow2.svg']) }}" alt="" class="pointer-events-none absolute start-[90.8%] top-[53%] w-[1.6%] opacity-20" aria-hidden="true">
    <img src="{{ route('hr.recruitment.assets', ['file' => 'arrow2.svg']) }}" alt="" class="pointer-events-none absolute start-[94.4%] top-[42%] w-[8.7%] opacity-30" aria-hidden="true">

    <span class="pointer-events-none absolute inset-x-0 bottom-[11%] h-[2.2%] bg-white" aria-hidden="true"></span>
</div>
