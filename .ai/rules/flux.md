---
paths:
  - '{config/blaze.php,resources/views/flux/**,app/Support/Blaze/**,app/Providers/AppServiceProvider.php,resources/views/components/**,modules/*/resources/views/components/**}'
---

# Flux

## Blaze compiles components but never folds them
Blaze is ON (config/blaze.php defaults `enabled` to true) and roughly halves render time: a page rendered ~931 anonymous components (sidebar items, pins, icons, table cells, buttons), which Blaze turns into plain PHP function calls (/hr/settings 1.1 s → 0.55 s locally, 2026-11-11).

Folding stays OFF for every component, including Flux's own `@blaze(fold: true)` templates: `App\Support\Blaze\NonFoldingBlazeManager` (bound in AppServiceProvider::register) swaps in `NonFoldingFolder`. Folding evaluated `{{ __('...') }}` at compile time and stored labels in whichever language compiled first, so English users saw Arabic. Never re-enable folding or `memo` unless every folded template is localized per request.

`AppServiceProvider::configureBlaze()` compiles resources/views/components and modules/*/resources/views/components, except the files in `BladeOnlyComponents`. A component file belongs on that list when it:
- reads the shared `$errors` bag (compiled components don't receive View::share() variables, so validation messages would silently disappear), or
- is pulled in with `@include(...)` / `view(...)` instead of an `<x-...>` tag (a compiled file only defines a function, so the include prints nothing).

After switching Blaze on/off or changing that list, clear BOTH caches: `php artisan view:clear` and `storage/framework/views/livewire` (Livewire's compiled pages otherwise call functions from the old cache: "Call to undefined function _<hash>()").

Side effect: Blade::render() tests of Flux inputs need View::share('errors', new ViewErrorBag).
