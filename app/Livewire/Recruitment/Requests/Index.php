<?php

namespace App\Livewire\Recruitment\Requests;

use App\Models\RecruitmentRequest;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Index extends Component
{
    public function render()
    {
        Gate::authorize('viewAny', RecruitmentRequest::class);

        return view('livewire.recruitment.requests.index');
    }
}
