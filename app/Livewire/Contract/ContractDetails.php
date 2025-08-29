<?php

namespace App\Livewire\Contract;

use App\Models\Contract;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class ContractDetails extends Component
{
    use AuthorizesRequests;

    public Contract $contract;

    public string $displayView = 'annual';

    public array $crumbs = [];

    public int $annualPage = 1;

    public int $totalPages = 1;

    protected $queryString = [
        'annualPage' => ['as' => 'p', 'except' => 1],
    ];

    private const TEMPLATE_PAGES = [
        'annual'  => 5,
        'trial'   => 1,
        'service' => 1,
    ];

    public function mount(Contract $contract): void
    {
        $contract->loadMissing([
            'employee',
            'employee.city',
            'position.center',
            'contractType',
            'department',
            'jobLevel',
        ]);

        $this->authorize('view', $contract);

        $this->contract    = $contract;
        $this->displayView = $contract->contractType->category ?: 'annual';

        $this->totalPages  = self::TEMPLATE_PAGES[$this->displayView] ?? 1;

        $requested        = (int) (request()->query('p', $this->annualPage) ?: 1);
        $this->annualPage = $this->clamp($requested);

        $employeeName = $contract->employee?->getTranslation('name', app()->getLocale())
            ?? __('general.na');

        $this->crumbs = [
            ['label' => trans_choice('general.employee-management', 2)],
            ['label' => trans_choice('general.contracts', 2), 'url' => route('contracts.index', [], false)],
            ['label' => $employeeName],
        ];
    }

    public function render()
    {
        return view('livewire.contract.contract-details', [
            'contract'     => $this->contract,
            'locale'       => app()->getLocale(),
            'displayView'  => $this->displayView,
            'partialPath'  => $this->partialPath(),
            'crumbs'       => $this->crumbs,
            'annualPage'   => $this->annualPage,
            'totalPages'   => $this->totalPages,
        ])->title(trans_choice('general.contracts', 1));
    }


    public function setAnnualPage(int $page): void
    {
        if (! $this->isPaginated()) return;

        $this->annualPage = $this->clamp($page);
        $this->scrollTop();
    }

    public function nextAnnualPage(): void
    {
        $this->setAnnualPage($this->annualPage + 1);
    }

    public function prevAnnualPage(): void
    {
        $this->setAnnualPage($this->annualPage - 1);
    }


    private function isPaginated(): bool
    {
        return ($this->displayView === 'annual') && $this->totalPages > 1;
    }

    private function clamp(int $p): int
    {
        return max(1, min($this->totalPages, $p));
    }

    private function partialPath(): string
    {
        return "livewire.contract.details.{$this->displayView}.{$this->displayView}";
    }

    private function scrollTop(): void
    {
        $this->dispatch('scroll-to-top');
    }
}
