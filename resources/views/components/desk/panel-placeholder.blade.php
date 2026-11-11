{{--
    A Desk panel while its data loads (IsDeskPanel::placeholder()): the same
    <section> on the same grid cells as the panel, titled, so the Desk paints at
    once and nothing moves when the panel arrives. If the load fails, desk-load.js
    shows the notice and its Retry button loads the panel again; the other panels
    are not touched.
--}}
<section class="dk-panel dk-panel-pending" id="dk-panel-{{ $id }}" style="--span:{{ $cols }};--rows:{{ $rows }};--i:{{ $index }}" data-cols="{{ $cols }}" data-rows="{{ $rows }}" data-panel="{{ $id }}" data-idx="{{ $index }}" data-desk-pending aria-busy="true" aria-labelledby="dk-ph-{{ $id }}">
    <header class="dk-ph">
        <div class="dk-ph-t">
            <h2 id="dk-ph-{{ $id }}" title="{{ $title }}">{{ $title }}</h2>
        </div>
    </header>
    <div class="dk-pb">
        <div class="dk-pending-lines" aria-hidden="true" data-desk-pending-lines>
            <span></span><span></span><span></span>
        </div>
        <p class="sr-only" data-desk-pending-lines>{{ __('desk.panel_loading', ['panel' => $title]) }}</p>
        <div class="dk-pending-failed" role="alert" hidden data-desk-failed>
            <p>{{ __('desk.panel_load_failed') }}</p>
            <button type="button" class="dk-btn" data-desk-retry>{{ __('desk.panel_retry') }}</button>
        </div>
    </div>
</section>
