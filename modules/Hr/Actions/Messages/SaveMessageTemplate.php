<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\Messages;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Hr\Models\MessageTemplate;

/**
 * Create or change an email / WhatsApp template. A chat has no subject and
 * nobody on CC, so a WhatsApp template keeps neither.
 */
final class SaveMessageTemplate
{
    /**
     * @param  array{name?: mixed, channel?: mixed, language?: mixed, subject?: mixed, body?: mixed, cc_manager?: mixed, is_active?: mixed}  $input
     *
     * @throws ValidationException
     */
    public function handle(?int $templateId, array $input): MessageTemplate
    {
        $user = Auth::user();
        abort_if($user === null, 403);

        $template = $templateId === null ? new MessageTemplate : MessageTemplate::query()->findOrFail($templateId);
        Gate::authorize($template->exists ? 'update' : 'create', $template->exists ? $template : MessageTemplate::class);

        $data = Validator::make($input, [
            'name' => ['required', 'string', 'max:120'],
            'channel' => ['required', Rule::in([MessageTemplate::EMAIL, MessageTemplate::WHATSAPP])],
            'language' => ['required', Rule::in(['en', 'ar'])],
            'subject' => [Rule::requiredIf(($input['channel'] ?? null) === MessageTemplate::EMAIL), 'nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:3000'],
            'cc_manager' => ['boolean'],
            'is_active' => ['boolean'],
        ], attributes: [
            'name' => __('hr_messages.fields.name'),
            'subject' => __('hr_messages.fields.subject'),
            'body' => __('hr_messages.fields.body'),
        ])->validate();

        if ($data['channel'] === MessageTemplate::WHATSAPP) {
            $data['subject'] = null;
            $data['cc_manager'] = false;
        }

        if (! $template->exists) {
            $data['created_by'] = $user->id;
        }

        $template->fill($data)->save();

        return $template;
    }
}
