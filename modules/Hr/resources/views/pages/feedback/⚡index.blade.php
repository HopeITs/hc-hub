<?php

use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Hr\Actions\Feedback\RecordFeedback;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\InAppFeedback;

/**
 * In-app feedback. Everyone can send a short note (a problem, an idea or a
 * question) with the page they came from, the language and their role; the
 * IT team and administrators read every note below the form, newest first.
 * Notes are deleted after a year.
 */
new #[Layout('layouts::desk'), Title('hr_communications.titles.feedback')] class extends Component {
    use WithPagination;

    public string $category = 'problem';

    public string $message = '';

    #[Locked]
    public ?string $fromRoute = null;

    #[Url(except: '')]
    public string $filter = '';

    public function mount(): void
    {
        Gate::authorize('create', InAppFeedback::class);

        $previous = url()->previous();

        if ($previous !== url()->current()) {
            try {
                $this->fromRoute = Route::getRoutes()->match(request()->create($previous))->getName();
            } catch (Throwable) {
                $this->fromRoute = null;
            }
        }
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function send(RecordFeedback $record): void
    {
        Gate::authorize('create', InAppFeedback::class);

        $this->validate([
            'category' => ['required', 'in:'.implode(',', RecordFeedback::Categories)],
            'message' => ['required', 'string', 'min:3', 'max:'.RecordFeedback::MaxLength],
        ], [], [
            'category' => __('hr_communications.feedback.category'),
            'message' => __('hr_communications.feedback.message'),
        ]);

        if (! $record->allows(auth()->user())) {
            $this->addError('message', __('hr_communications.feedback.too_many'));

            return;
        }

        $record->handle(auth()->user(), $this->category, $this->message, $this->fromRoute);
        $this->reset('message');
        unset($this->notes);
        Flux::toast(variant: 'success', text: __('hr_communications.feedback.sent'));
    }

    public function remove(int $id): void
    {
        $note = InAppFeedback::query()->findOrFail($id);
        Gate::authorize('delete', $note);
        $note->delete();
        unset($this->notes);
    }

    #[Computed]
    public function canRead(): bool
    {
        return auth()->user()->can('viewAny', InAppFeedback::class);
    }

    #[Computed]
    public function notes(): ?LengthAwarePaginator
    {
        if (! $this->canRead) {
            return null;
        }

        return InAppFeedback::query()
            ->select(['id', 'user_id', 'category', 'message', 'route_name', 'locale', 'role_name', 'created_at'])
            ->with(['user:id,email,employee_id', 'user.employee:'.implode(',', Employee::NameColumns)])
            ->when(in_array($this->filter, RecordFeedback::Categories, true), fn ($query) => $query->where('category', $this->filter))
            ->latest('id')
            ->paginate(20);
    }
}; ?>

<x-desk.register heading-id="hr-feedback-heading" :title="__('hr_communications.titles.feedback')" :count="$this->notes?->total()">
    <form wire:submit="send" class="grid max-w-2xl gap-4" novalidate>
        <p class="text-secondary-medium dark:text-ink-muted">{{ __('hr_communications.feedback.intro') }}</p>
        <x-ui.form-segmented
            :label="__('hr_communications.feedback.category')"
            wire:model="category"
            :options="collect(RecordFeedback::Categories)->map(fn (string $value): array => ['value' => $value, 'label' => __('hr_communications.feedback.categories.'.$value)])->all()"
            required
        />
        <x-ui.form-textarea :label="__('hr_communications.feedback.message')" wire:model="message" rows="5" :maxlength="RecordFeedback::MaxLength" required :help="__('hr_communications.feedback.privacy')" />
        <div>
            <x-ui.button type="submit" tone="primary" data-test="hr-feedback-send">{{ __('hr_communications.feedback.send') }}</x-ui.button>
        </div>
    </form>

    @if ($this->canRead)
        @php($notes = $this->notes)
        <section class="mt-10 grid gap-4" aria-labelledby="hr-feedback-received">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <h2 id="hr-feedback-received" class="font-semibold">{{ __('hr_communications.feedback.received') }}</h2>
                <x-ui.header-filter-select :label="__('hr_communications.feedback.category')" wire:model.live="filter" :value="$filter">
                    <option value="">{{ __('hr_communications.feedback.all_categories') }}</option>
                    @foreach (RecordFeedback::Categories as $value)
                        <option value="{{ $value }}">{{ __('hr_communications.feedback.categories.'.$value) }}</option>
                    @endforeach
                </x-ui.header-filter-select>
            </div>

            @if ($notes->isEmpty())
                <x-ui.empty-state icon="chat-bubble-left-right" :heading="__('hr_communications.feedback.none')" />
            @else
                <x-ui.data-table :caption="__('hr_communications.feedback.received')">
                    <x-slot:head>
                        <th class="w-40!">{{ __('hr_communications.feedback.columns.from') }}</th>
                        <th class="w-28!">{{ __('hr_communications.feedback.category') }}</th>
                        <th>{{ __('hr_communications.feedback.message') }}</th>
                        <th class="w-44!">{{ __('hr_communications.feedback.columns.context') }}</th>
                        <th class="w-32!">{{ __('hr_communications.feedback.columns.when') }}</th>
                        <th class="w-12!"><span class="sr-only">{{ __('common.delete') }}</span></th>
                    </x-slot:head>
                    @foreach ($notes as $note)
                        <tr wire:key="feedback-{{ $note->id }}">
                            <x-ui.data-table.cell>{{ $note->user?->employee?->name ?? $note->user?->email ?? '—' }}</x-ui.data-table.cell>
                            <x-ui.data-table.cell :label="__('hr_communications.feedback.category')">{{ $note->category ? __('hr_communications.feedback.categories.'.$note->category) : '—' }}</x-ui.data-table.cell>
                            <x-ui.data-table.cell :label="__('hr_communications.feedback.message')"><span class="whitespace-pre-line" dir="auto">{{ $note->message }}</span></x-ui.data-table.cell>
                            <x-ui.data-table.cell :label="__('hr_communications.feedback.columns.context')">
                                <span class="type-meta">{{ $note->route_name ?? '—' }} · {{ strtoupper($note->locale) }}@if ($note->role_name) · {{ $note->role_name }}@endif</span>
                            </x-ui.data-table.cell>
                            <x-ui.data-table.cell :label="__('hr_communications.feedback.columns.when')">{{ $note->created_at?->diffForHumans() }}</x-ui.data-table.cell>
                            <x-ui.data-table.cell class="@3xl:text-end">
                                <flux:button size="xs" variant="ghost" icon="trash" wire:click="remove({{ $note->id }})" wire:confirm="{{ __('hr_communications.feedback.remove_confirm') }}" :aria-label="__('common.delete')" />
                            </x-ui.data-table.cell>
                        </tr>
                    @endforeach
                </x-ui.data-table>
                <flux:pagination :paginator="$notes" />
            @endif
        </section>
    @endif
</x-desk.register>
