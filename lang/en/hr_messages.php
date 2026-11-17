<?php

return [
    'title' => 'Message templates',
    'lead' => 'Email and WhatsApp texts with placeholders. Nothing is sent by the system: a template opens as a new message you read and edit first.',
    'new' => 'New template',
    'edit' => 'Edit template',
    'fields' => [
        'name' => 'Name',
        'channel' => 'Channel',
        'language' => 'Language',
        'subject' => 'Subject',
        'body' => 'Message',
        'cc_manager' => "Copy the person's direct manager",
        'active' => 'Active',
    ],
    'channels' => [
        'email' => 'Email',
        'whatsapp' => 'WhatsApp',
    ],
    'languages' => [
        'en' => 'English',
        'ar' => 'Arabic',
    ],
    'variables_help' => 'Placeholders you can use: :variables',
    'on' => 'On',
    'off' => 'Off',
    'toggle_name' => 'Switch :name on or off',
    'saved' => 'Template saved.',
    'deleted' => 'Template deleted.',
    'delete_title' => 'Delete :name?',
    'delete_message' => 'The template is removed for everyone. Messages already opened from it are not affected.',
    'empty' => 'No templates yet',
    'empty_help' => 'Create one, for example "Contract ending", and it appears on every person\'s profile.',
    'use_template' => 'Use a template',
    'no_address' => 'No address',
];
