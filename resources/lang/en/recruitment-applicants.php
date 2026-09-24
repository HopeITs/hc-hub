<?php

/*
 * Team 2 — Phase 2 strings (applicant intake test form, campaign invites).
 * Kept apart from recruitment.php, which Team 1 edits constantly: when both
 * teams appended to that file the merge dropped every one of these keys.
 */
return [
    // Submission service errors (generic on purpose — no enumeration).
    'invalid_or_expired_link' => 'This application link is invalid or has expired.',
    'campaign_not_accepting' => 'This campaign is not currently accepting applications.',
    'invitation_required' => 'This campaign is invitation-only and this email address was not found on the invitation list.',
    'already_applied' => 'An application already exists for this email address on this campaign.',
    'missing_required_field' => 'The :field field is required.',
    'public_apply_fix_errors' => 'Your application could not be sent. Please correct the highlighted fields below.',
    'public_apply_uploading' => 'Uploading...',
    'public_apply_privacy_required' => 'You must accept the privacy notice to apply.',

    // Test application form (/careers-test/{token}).
    'public_apply_title' => 'Apply for this position',
    'public_apply_full_name' => 'Full name',
    'public_apply_email' => 'Email address',
    'public_apply_phone' => 'Phone number',
    'public_apply_city' => 'City',
    'public_apply_privacy_consent' => 'I agree that my information will be used to process this application.',
    'public_apply_submit' => 'Submit application',
    'public_apply_thanks_title' => 'Application received',
    'public_apply_thanks_body' => 'Thank you for applying. Our recruitment team will review your application and contact you if you are shortlisted.',

    // Campaign invites screen.
    'invites_title' => 'Campaign invitations',
    'invites_description' => 'Only people added here may submit an application when this campaign is invitation-only.',
    'invite_full_name' => 'Full name',
    'invite_email' => 'Email address',
    'invite_add' => 'Add invite',
    'invite_added' => 'Invitation saved.',
    'invite_status' => 'Status',
    'invite_used_at' => 'Applied at',
    'invite_cancel' => 'Cancel',
    'invite_cancel_confirm' => 'Cancel this invitation? The person will no longer be able to apply.',
    'invite_cancelled' => 'Invitation cancelled.',
    'invite_status_pending' => 'Pending',
    'invite_status_sent' => 'Sent',
    'invite_status_used' => 'Applied',
    'invite_status_cancelled' => 'Cancelled',
    'invite_already_exists' => 'This email is already invited to this campaign.',
    'invite_already_used' => 'This person has already applied using this invitation.',
    'invite_not_invitation_only' => 'Invites apply only to invitation-only campaigns.',
];
