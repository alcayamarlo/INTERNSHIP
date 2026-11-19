<?php

namespace App\Http\Requests\Messaging;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $recipient = $this->route('user');
        $sender = $this->user();

        return $recipient instanceof User
            && $sender instanceof User
            && $sender->can('message.send', $recipient);
    }

    public function rules(): array
    {
        return [
            // Text, a photo, or both. A photo is checked by its real content (not just the extension); SVG is not allowed.
            'body' => ['nullable', 'string', 'max:5000', 'required_without:attachment'],
            'attachment' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'submission_id' => ['required', 'uuid'],
            // Reply: must be a message in this same chat (checked in MessageController::store).
            'reply_to_id' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required_without' => 'Type a message or attach a photo.',
            'attachment.image' => 'Only photos can be sent (JPG, PNG, WEBP or GIF).',
            'attachment.mimes' => 'Only photos can be sent (JPG, PNG, WEBP or GIF).',
            'attachment.max' => 'The photo must be 5 MB or smaller.',
            'attachment.uploaded' => 'The photo could not be uploaded. It may be larger than the server allows.',
            'body.max' => 'Message must not exceed 5000 characters.',
        ];
    }

    /** 403 with the specific reason from MessagingEligibility (e.g. the application is not Reviewed yet). */
    protected function failedAuthorization(): void
    {
        $recipient = $this->route('user');
        $sender = $this->user();

        if ($recipient instanceof User && $sender instanceof User) {
            $reason = Gate::forUser($sender)->inspect('message.send', $recipient)->message();
            if ($reason) {
                throw new AuthorizationException($reason);
            }
        }

        parent::failedAuthorization();
    }
}
