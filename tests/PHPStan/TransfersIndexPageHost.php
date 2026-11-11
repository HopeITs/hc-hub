<?php

declare(strict_types=1);

namespace Tests\PHPStan;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Tracking\Livewire\Concerns\FiltersFinanceRegister;
use Modules\Tracking\Livewire\Concerns\ManagesTransferForm;
use Modules\Tracking\Livewire\Concerns\Transfers\ExportsTransferRegister;
use Modules\Tracking\Livewire\Concerns\Transfers\FiltersTransferRegister;
use Modules\Tracking\Livewire\Concerns\Transfers\PresentsTransferRegister;
use Modules\Tracking\Livewire\Concerns\Transfers\ReadsTransferBoard;
use Modules\Tracking\Livewire\Concerns\Transfers\ReadsTransferLedger;
use Modules\Tracking\Livewire\Concerns\Transfers\ReadsTransferRegister;

/**
 * Stands in for transfers/index, with every trait that page uses.
 *
 * The real page is a single-file Livewire component inside Blade, which
 * PHPStan cannot read, so without this class the transfers register traits
 * are never analysed. It declares the page's own members the traits read.
 * Never instantiated.
 */
abstract class TransfersIndexPageHost extends Component
{
    use ExportsTransferRegister;
    use FiltersFinanceRegister;
    use FiltersTransferRegister;
    use ManagesTransferForm;
    use PresentsTransferRegister;
    use ReadsTransferBoard;
    use ReadsTransferLedger;
    use ReadsTransferRegister;
    use WithPagination;

    /** @var list<string> */
    public const SORTS = ['attention', 'shortfall', 'date', 'amount'];

    /** @var list<string> */
    public const STAGES = ['overdue', 'transit', 'upcoming', 'received', 'failed', 'short', 'unrecorded'];

    /** @var list<string> */
    public const FLAGS = ['converted', 'no_account'];

    /** @var list<string> */
    public const GROUPS = ['agreement', 'donor', 'account', 'month'];

    /** @var list<string> */
    public const DATE_BASES = ['scheduled', 'sent', 'received'];

    public string $search = '';

    public string $donor = '';

    public string $account = '';

    public string $currency = '';

    public string $stage = '';

    public string $flag = '';

    public string $dateBasis = 'scheduled';

    public string $from = '';

    public string $to = '';

    public string $min = '';

    public string $max = '';

    public string $group = '';

    public string $sort = 'attention';

    public string $agreement = '';
}
