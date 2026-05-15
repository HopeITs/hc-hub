<?php

namespace App\Livewire\Contract;

use App\Models\Contract;
use Livewire\Component;

class ContractDetails extends Component
{
    public Contract $contract;

    public string $displayView = 'annual';
    public array  $crumbs      = [];
    public int    $pageNo      = 1;
    public int    $totalPages  = 1;

    protected $queryString = [
        'pageNo' => ['as' => 'p', 'except' => 1],
    ];

    private const TEMPLATE_PAGES = [
        'annual'  => 4,
        'trial'   => 4,
        'service' => 4,
    ];

    public function mount(Contract $contract): void
    {
        $contract->loadMissing([
            'employee',
            'employee.city',
            'position.center',
            'contractType',
            'position.department',
            'position.jobLevel',
            'position.program',
        ]);

        $this->contract = $contract;

        // 🔒 Safe category resolution (no crashes if contractType is null or misconfigured)
        $category = $contract->contractType->category
            ?? match ((int)($contract->contractType->type ?? $contract->contract_type_id ?? 0)) {
                2 => 'trial',
                3 => 'service',
                default => 'annual',
            };

        $this->displayView = in_array($category, ['annual','trial','service'], true) ? $category : 'annual';
        $this->totalPages  = self::TEMPLATE_PAGES[$this->displayView] ?? 1;

        // Keep requested page within bounds
        $requested   = (int) (request()->query('p', $this->pageNo) ?: 1);
        $this->pageNo = $this->clamp($requested);

        // 🔒 Guard employee name translation (no crash if employee null or no translation)
        $locale       = app()->getLocale();
        $employeeName = $contract->employee?->name ?? __('general.na');

        $this->crumbs = [
            ['label' => trans_choice('general.employee-management', 2)],
            ['label' => trans_choice('general.contracts', 2), 'url' => route('contracts.index', [], false)],
            ['label' => $employeeName],
        ];
    }

    public function render()
    {
        return view('livewire.contract.contract-details', [
            'contract'    => $this->contract,
            'locale'      => app()->getLocale(),
            'displayView' => $this->displayView,
            'partialPath' => $this->partialPath(),
            'crumbs'      => $this->crumbs,
            'pageNo'      => $this->pageNo,
            'totalPages'  => $this->totalPages,
        ])->title(trans_choice('general.contracts', 1));
    }

    public function setPage(int $page): void
    {
        if ($this->totalPages > 1) {
            $this->pageNo = $this->clamp($page);
            $this->scrollTop();
        }
    }
    public function nextPage(): void { $this->setPage($this->pageNo + 1); }
    public function prevPage(): void { $this->setPage($this->pageNo - 1); }

    private function clamp(int $p): int
    {
        return max(1, min($this->totalPages, $p));
    }

    private function partialPath(): string
    {
        // Renders: livewire.contract.details.{annual|trial|service}.{same}
        return "livewire.contract.details.{$this->displayView}.{$this->displayView}";
    }

    private function scrollTop(): void
    {
        $this->dispatch('scroll-to-top');
    }

    private function isPaginated(): bool
    {
        return ($this->displayView === 'annual') && $this->totalPages > 1;
    }
}
