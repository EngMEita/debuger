<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Enable Debuger
    |--------------------------------------------------------------------------
    |
    | Quickly disable the package without uninstalling it. When set to false
    | the default Laravel exception handler behavior is used.
    |
    */
    'enabled' => env('DEBUGER_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | User Facing Message
    |--------------------------------------------------------------------------
    |
    | This text is shown on the friendly error page and JSON responses instead
    | of exposing the actual exception details.
    |
    */
    'user_message' => env('DEBUGER_USER_MESSAGE', 'Something went wrong. Please try again later.'),

    /*
    |--------------------------------------------------------------------------
    | Email Settings
    |--------------------------------------------------------------------------
    |
    | The email address that should receive exception reports and the subject
    | line used for those messages.
    |
    */
    'mail' => [
        'to' => env('DEBUGER_MAIL_TO'),
        'subject' => env('DEBUGER_MAIL_SUBJECT', 'Application Exception'),
        'from' => env('DEBUGER_MAIL_FROM'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sensitive Request Fields
    |--------------------------------------------------------------------------
    |
    | Request input keys that will be stripped from the error report email to
    | prevent leaking secrets.
    |
    */
    'hidden_fields' => [
        '_token',
        'password',
        'password_confirmation',
    ],

    /*
    |--------------------------------------------------------------------------
    | Reference Prefix
    |--------------------------------------------------------------------------
    |
    | A short prefix placed before the generated reference hash included in the
    | error page and email to help correlate user reports with log entries.
    |
    */
    'reference_prefix' => env('DEBUGER_REFERENCE_PREFIX', 'ERR-'),
];
