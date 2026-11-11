<?php

declare(strict_types=1);

namespace Tests\PHPStan;

use Livewire\Component;
use Modules\Tracking\Livewire\Concerns\ManagesAgreementForm;

/**
 * Stands in for the pages that record a funding agreement.
 *
 * The real pages are single-file Livewire components inside Blade, which
 * PHPStan cannot read, so without this class the trait is never analysed.
 * Never instantiated.
 */
abstract class AgreementFormHost extends Component
{
    use ManagesAgreementForm;
}
