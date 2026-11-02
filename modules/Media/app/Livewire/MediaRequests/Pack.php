<?php

namespace Modules\Media\Livewire\MediaRequests;

use App\Models\Program;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Modules\Media\Services\MediaRequestWorkflow;
use Modules\Media\Support\HcPack;

/**
 * HC_PACK: logos and ready templates. Tick the files you need and ask for
 * them; the request records who asked and when, and any team member sends
 * the files from the shared drive.
 */
class Pack extends Component
{
    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'group')]
    public string $group = '';

    /** @var array<int, string> */
    public array $selected = [];

    public string $note = '';

    public function requestFiles(MediaRequestWorkflow $workflow): void
    {
        $request = $workflow->requestPack(auth()->user(), $this->selected, $this->note);

        $this->redirectRoute('media-requests.show', $request);
    }

    public function render(): View
    {
        $pack = app(HcPack::class);
        $programs = Program::query()->get(['id', 'code', 'slug']);
        $searching = trim($this->search) !== '' || $this->group !== '';

        return view('livewire.media-requests.pack', [
            'groups' => HcPack::GROUPS,
            'counts' => $pack->groupCounts(),
            'files' => $searching ? $pack->search($this->search, $this->group ?: null, $programs) : collect(),
            'searching' => $searching,
            'chosen' => $pack->find($this->selected),
        ]);
    }
}
