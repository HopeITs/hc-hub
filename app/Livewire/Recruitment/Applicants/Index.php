<?php

namespace App\Livewire\Recruitment\Applicants;

use App\Models\RecruitmentApplicant;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Index extends Component
{
    public function render()
    {
        Gate::authorize('viewAny', RecruitmentApplicant::class);

        return view('livewire.recruitment.applicants.index');
    }
}
