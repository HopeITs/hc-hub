<?php

namespace App\Livewire\Recruitment\Interviews;

use App\Models\RecruitmentInterview;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Phase 2 Task 7: the interviews page — a list or a month calendar of every
 * interview the user may see (their own as a participant, or those in their
 * recruitment scope). Nothing is changed from here; each interview opens on
 * its own page, where the actions are.
 */
class Index extends Component
{
    #[Url]
    public string $view = 'list';

    public function mount(): void
    {
        Gate::authorize('viewAny', RecruitmentInterview::class);

        if (! in_array($this->view, ['list', 'calendar'], true)) {
            $this->view = 'list';
        }
    }

    public function render()
    {
        return view('livewire.recruitment.interviews.index');
    }
}
