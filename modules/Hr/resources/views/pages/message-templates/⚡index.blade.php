<?php

use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Modules\Hr\Actions\Messages\SaveMessageTemplate;
use Modules\Hr\Models\MessageTemplate;
use Modules\Hr\Support\Employees\MessageTemplateRenderer;

/*
 * HR's email and WhatsApp templates (message-templates.manage). Writing one
 * sends nothing: a template is only filled in and opened as a new message
 * from a person's profile (MessageTemplateRenderer).
 */
new #[Layout('layouts::desk'), Title('hr_messages.title')] class extends Component {
    public bool $showFormModal = false;

    #[Locked]
    public ?int $editingId = null;

    public string $name = '';

    public string $channel = MessageTemplate::EMAIL;

    public string $language = 'en';

    public string $subject = '';

    public string $body = '';

    public bool $ccManager = false;

    public bool $isActive = true;

    public bool $showDeleteModal = false;

    #[Locked]
    public ?int $deletingId = null;

    public string $deletingName = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', MessageTemplate::class);
    }

    /**
     * @return Collection<int, MessageTemplate>
     */
    #[Computed]
    public function templates(): Collection
    {
        return MessageTemplate::query()->orderBy('channel')->orderBy('name')->get();
    }

    public function openCreateModal(): void
    {
        Gate::authorize('create', MessageTemplate::class);

        $this->resetForm();
        $this->showFormModal = true;
    }

    public function openEditModal(int $templateId): void
    {
        $template = MessageTemplate::query()->findOrFail($templateId);
        Gate::authorize('update', $template);

        $this->resetForm();
        $this->editingId = $template->id;
        $this->name = $template->name;
        $this->channel = $template->channel;
        $this->language = $template->language;
        $this->subject = (string) $template->subject;
        $this->body = $template->body;
        $this->ccManager = $template->cc_manager;
        $this->isActive = $template->is_active;
        $this->showFormModal = true;
    }

    public function save(): void
    {
        try {
            app(SaveMessageTemplate::class)->handle($this->editingId, [
                'name' => $this->name,
                'channel' => $this->channel,
                'language' => $this->language,
                'subject' => $this->subject === '' ? null : $this->subject,
                'body' => $this->body,
                'cc_manager' => $this->ccManager,
                'is_active' => $this->isActive,
            ]);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            throw \Illuminate\Validation\ValidationException::withMessages(
                collect($exception->errors())->mapWithKeys(fn (array $messages, string $key): array => [match ($key) {
                    'cc_manager' => 'ccManager',
                    'is_active' => 'isActive',
                    default => $key,
                } => $messages])->all(),
            );
        }

        $this->closeFormModal();
        unset($this->templates);

        Flux::toast(variant: 'success', text: __('hr_messages.saved'));
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    public function toggleActive(int $templateId): void
    {
        $template = MessageTemplate::query()->findOrFail($templateId);
        Gate::authorize('update', $template);

        $template->update(['is_active' => ! $template->is_active]);
        unset($this->templates);
    }

    public function confirmDelete(int $templateId): void
    {
        $template = MessageTemplate::query()->findOrFail($templateId);
        Gate::authorize('delete', $template);

        $this->deletingId = $template->id;
        $this->deletingName = $template->name;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $template = MessageTemplate::query()->findOrFail((int) $this->deletingId);
        Gate::authorize('delete', $template);

        $template->delete();
        $this->cancelDelete();
        unset($this->templates);

        Flux::toast(variant: 'success', text: __('hr_messages.deleted'));
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deletingId = null;
        $this->deletingName = '';
    }

    private function resetForm(): void
    {
        $this->resetValidation();
        $this->reset(['editingId', 'name', 'subject', 'body', 'ccManager']);
        $this->channel = MessageTemplate::EMAIL;
        $this->language = 'en';
        $this->isActive = true;
    }
};
?>

@php
    $templates = $this->templates;
    $channelLabel = fn (string $channel): string => __('hr_messages.channels.'.$channel);
@endphp

<x-desk.register class="@container" heading-id="hr-message-templates-heading" :title="__('hr_messages.title')" :count="$templates->count()" data-hr-message-templates>
    <x-slot:actions>
        <x-ui.button icon="envelope" x-on:click="Livewire.dispatch('announcement-compose')" data-test="open-announcements">{{ __('hr_messages.announcements.title') }}</x-ui.button>
        <x-ui.button tone="primary" icon="plus" wire:click="openCreateModal" data-test="create-message-template" data-quick-create>{{ __('hr_messages.new') }}</x-ui.button>
    </x-slot:actions>

    <p class="mb-3 text-sm text-secondary-medium dark:text-ink-muted">{{ __('hr_messages.lead') }}</p>

    @if ($templates->isEmpty())
        <x-ui.empty-state icon="chat-bubble-left-right" :heading="__('hr_messages.empty')" :body="__('hr_messages.empty_help')">
            <x-slot:action>
                <x-ui.button tone="primary" icon="plus" wire:click="openCreateModal">{{ __('hr_messages.new') }}</x-ui.button>
            </x-slot:action>
        </x-ui.empty-state>
    @else
        <x-ui.data-table :caption="__('hr_messages.title')">
            <x-slot:head>
                <th>{{ __('hr_messages.fields.name') }}</th>
                <th class="w-32!">{{ __('hr_messages.fields.channel') }}</th>
                <th class="w-28!">{{ __('hr_messages.fields.language') }}</th>
                <th class="w-28!">{{ __('hr_messages.fields.active') }}</th>
                <th class="w-24!"><span class="sr-only">{{ __('common.actions') }}</span></th>
            </x-slot:head>

            @foreach ($templates as $template)
                <tr wire:key="message-template-{{ $template->id }}" data-message-template="{{ $template->id }}">
                    <td><span class="font-semibold" dir="auto">{{ $template->name }}</span></td>
                    <td>{{ $channelLabel($template->channel) }}</td>
                    <td>{{ __('hr_messages.languages.'.$template->language) }}</td>
                    <td>
                        <button type="button" class="text-sm underline underline-offset-4" wire:click="toggleActive({{ $template->id }})" aria-label="{{ __('hr_messages.toggle_name', ['name' => $template->name]) }}">
                            {{ $template->is_active ? __('hr_messages.on') : __('hr_messages.off') }}
                        </button>
                    </td>
                    <td>
                        <span class="flex items-center justify-end gap-1">
                            <x-ui.button tone="ghost" size="sm" icon="pencil-square" wire:click="openEditModal({{ $template->id }})" :aria-label="__('common.edit_name', ['name' => $template->name])" />
                            <x-ui.button tone="ghost" size="sm" icon="trash" class="text-status-critical!" wire:click="confirmDelete({{ $template->id }})" :aria-label="__('common.delete_name', ['name' => $template->name])" />
                        </span>
                    </td>
                </tr>
            @endforeach
        </x-ui.data-table>
    @endif

    <x-ui.modal wire:model="showFormModal" :title="$editingId === null ? __('hr_messages.new') : __('hr_messages.edit')" submit="save" close="closeFormModal" required-note>
        @if ($showFormModal)
            <div class="grid gap-y-3">
                <x-ui.form-input wire:model="name" :label="__('hr_messages.fields.name')" maxlength="120" required />
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-ui.form-select wire:model.live="channel" :label="__('hr_messages.fields.channel')" required>
                        <option value="email">{{ $channelLabel('email') }}</option>
                        <option value="whatsapp">{{ $channelLabel('whatsapp') }}</option>
                    </x-ui.form-select>
                    <x-ui.form-select wire:model="language" :label="__('hr_messages.fields.language')" required>
                        <option value="en">{{ __('hr_messages.languages.en') }}</option>
                        <option value="ar">{{ __('hr_messages.languages.ar') }}</option>
                    </x-ui.form-select>
                </div>
                @if ($channel === 'email')
                    <x-ui.form-input wire:model="subject" :label="__('hr_messages.fields.subject')" maxlength="255" required />
                @endif
                <x-ui.form-textarea wire:model="body" :label="__('hr_messages.fields.body')" rows="8" maxlength="3000" dir="auto" required
                    :help="__('hr_messages.variables_help', ['variables' => collect(MessageTemplateRenderer::VARIABLES)->map(fn (string $variable): string => '{'.$variable.'}')->implode(' ')])" />
                @if ($channel === 'email')
                    <flux:checkbox wire:model="ccManager" :label="__('hr_messages.fields.cc_manager')" />
                @endif
                <flux:checkbox wire:model="isActive" :label="__('hr_messages.fields.active')" />
            </div>
        @endif

        <x-slot:actions>
            <x-ui.button type="submit" tone="primary" data-test="save-message-template">{{ __('common.save_changes') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.modal>

    <livewire:hr::message-templates.announcements />

    <x-ui.confirm-modal
        wire:model="showDeleteModal"
        close="cancelDelete"
        :title="__('hr_messages.delete_title', ['name' => $deletingName])"
        :message="__('hr_messages.delete_message')"
        confirm="delete"
        :confirm-label="__('common.delete')"
        confirm-test="confirm-delete-message-template"
    />
</x-desk.register>
