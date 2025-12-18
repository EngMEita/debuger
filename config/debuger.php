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
    // Support the common "DEBUGGER_*" spelling as a fallback.
    'enabled' => env('DEBUGER_ENABLED', env('DEBUGGER_ENABLED', true)),

    /*
    |--------------------------------------------------------------------------
    | User Facing Message
    |--------------------------------------------------------------------------
    |
    | This text is shown on the friendly error page and JSON responses instead
    | of exposing the actual exception details.
    |
    */
    'user_message' => env(
        'DEBUGER_USER_MESSAGE',
        env('DEBUGGER_USER_MESSAGE', 'Something went wrong. Please try again later.')
    ),

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
        'to' => env('DEBUGER_MAIL_TO', env('DEBUGGER_MAIL_TO')),
        'subject' => env('DEBUGER_MAIL_SUBJECT', env('DEBUGGER_MAIL_SUBJECT', 'Application Exception')),
        'from' => env('DEBUGER_MAIL_FROM', env('DEBUGGER_MAIL_FROM')),
        // Allow emailing exceptions thrown from CLI (queue workers, scheduler, artisan, ...).
        'send_in_console' => env('DEBUGER_MAIL_IN_CONSOLE', env('DEBUGGER_MAIL_IN_CONSOLE', true)),
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
    'reference_prefix' => env('DEBUGER_REFERENCE_PREFIX', env('DEBUGGER_REFERENCE_PREFIX', 'ERR-')),
];
