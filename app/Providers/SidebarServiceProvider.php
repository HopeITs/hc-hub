<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use App\Models\Checklist;
use Illuminate\Support\Facades\Auth;

class SidebarServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer('livewire.layout.sidebar', function ($view) {
            $employee = Auth::user()->profile ?? null;
            $employeeId = $employee->id ?? null;
            $departmentName = $employee?->contracts()->with('position.department')->first()?->position?->department?->name;

            $isDeliverResponsible = in_array($departmentName, ['IT', 'Communication', 'HR']);

            $pendingDeliversCount = $employeeId
                ? Checklist::where('delivered_id', $employeeId)
                    ->where('delivered_status', '!=', 'delivered')
                    ->count()
                : 0;

            $pendingReturnsCount = $employeeId
                ? Checklist::where('receiver_id', $employeeId)
                    ->where('returned_status', 'pending')
                    ->count()
                : 0;

            $view->with(compact(
                'employee',
                'employeeId',
                'departmentName',
                'isDeliverResponsible',
                'pendingDeliversCount',
                'pendingReturnsCount'
            ));
        });
    }
}
