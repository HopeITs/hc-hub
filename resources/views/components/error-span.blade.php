{{--
    Kept in the DOM even when empty, on purpose. A live region has to exist
    before the text arrives for the update to be announced, and the element
    also reserves its own vertical space — removing it when empty would shift
    the surrounding form layout.
--}}
<span role="alert"
      aria-live="polite"
      class="mt-3 text-sm text-red-600 font-medium block ">{{$slot}}</span>
