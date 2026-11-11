<?php

use App\Jobs\RunAiPrompt;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Modules\Hr\Support\Assistant\HrAssistant;

/**
 * Ask HR: a question about your own leave or the coming holidays, answered
 * by the hub's AI from your own facts. Answers arrive from the "ai" queue;
 * the page checks every few seconds while one is pending. Questions and
 * answers stay on this page only.
 */
new #[Layout('layouts::desk'), Title('hr_communications.titles.assistant')] class extends Component {
    public string $question = '';

    /**
     * @var list<array{question: string, key: string, status: string, text: string|null}>
     */
    #[Locked]
    public array $exchanges = [];

    public function mount(HrAssistant $assistant): void
    {
        abort_unless((auth()->user()->employee_id !== null || auth()->user()->isAdministrator()) && $assistant->isAvailable(), 403);
    }

    public function ask(HrAssistant $assistant): void
    {
        $this->validate(['question' => ['required', 'string', 'min:3', 'max:'.HrAssistant::MaxQuestion]], [], ['question' => __('hr_communications.assistant.question')]);

        $limit = 'hr-assistant:'.auth()->id();

        if (RateLimiter::tooManyAttempts($limit, 20)) {
            $this->addError('question', __('hr_communications.assistant.too_many'));

            return;
        }

        RateLimiter::hit($limit, 3600);

        $this->exchanges[] = [
            'question' => trim($this->question),
            'key' => $assistant->ask(auth()->user(), $this->question),
            'status' => 'pending',
            'text' => null,
        ];
        $this->exchanges = array_slice($this->exchanges, -10);
        $this->reset('question');
    }

    public function collect(): void
    {
        foreach ($this->exchanges as $index => $exchange) {
            if ($exchange['status'] !== 'pending') {
                continue;
            }

            $result = RunAiPrompt::resultFor($exchange['key'], auth()->user());

            if ($result === null) {
                $this->exchanges[$index]['status'] = 'failed';
            } elseif ($result['status'] !== 'pending') {
                $this->exchanges[$index]['status'] = $result['status'];
                $this->exchanges[$index]['text'] = $result['text'];
            }
        }
    }

    public function waiting(): bool
    {
        return collect($this->exchanges)->contains('status', 'pending');
    }
}; ?>

<x-desk.register heading-id="hr-assistant-heading" :title="__('hr_communications.titles.assistant')">
    <x-slot:figures>{{ __('hr_communications.assistant.intro') }}</x-slot:figures>

    <div class="grid max-w-3xl gap-6" @if ($this->waiting()) wire:poll.3s="collect" @endif>
        @foreach ($exchanges as $index => $exchange)
            <div class="grid gap-2" wire:key="exchange-{{ $exchange['key'] }}">
                <p class="justify-self-end rounded-lg bg-secondary-soft px-4 py-2 dark:bg-surface-2" dir="auto">{{ $exchange['question'] }}</p>
                <div class="rounded-lg border border-secondary-soft px-4 py-2 dark:border-line" aria-live="polite">
                    @if ($exchange['status'] === 'pending')
                        <p class="text-secondary-medium dark:text-ink-muted">{{ __('hr_communications.assistant.thinking') }}</p>
                    @elseif ($exchange['status'] === 'done')
                        <p class="whitespace-pre-line" dir="auto">{{ $exchange['text'] }}</p>
                    @else
                        <p class="text-status-critical">{{ __('hr_communications.assistant.failed') }}</p>
                    @endif
                </div>
            </div>
        @endforeach

        <form wire:submit="ask" class="grid gap-3" novalidate>
            <x-ui.form-textarea :label="__('hr_communications.assistant.question')" wire:model="question" rows="3" :maxlength="HrAssistant::MaxQuestion" :help="__('hr_communications.assistant.privacy')" required />
            <div>
                <x-ui.button type="submit" tone="primary" data-test="hr-assistant-ask">{{ __('hr_communications.assistant.ask') }}</x-ui.button>
            </div>
        </form>
    </div>
</x-desk.register>
