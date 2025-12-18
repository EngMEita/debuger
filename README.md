# Debuger

Laravel package that replaces the default exception handling flow with a friendly error page for end users while emailing the full stack trace and request context to you.

## Installation

1. Require the package:

```bash
composer require meita/debuger
```

2. (Optional) Publish the config and views:

```bash
php artisan vendor:publish --tag=debuger-config
php artisan vendor:publish --tag=debuger-views
```

The service provider is auto-discovered. No manual registration is needed.

## Configuration

Set your desired values in `config/debuger.php` or via environment variables:

- `DEBUGER_ENABLED` (default `true`) – turn the package on or off.
- `DEBUGER_USER_MESSAGE` – text shown to users instead of the real error.
- `DEBUGER_MAIL_TO` – recipient(s) for exception reports (required to send emails). Supports a single address or a comma/semicolon separated list.
- `DEBUGER_MAIL_SUBJECT` – subject prefix for exception emails.
- `DEBUGER_MAIL_FROM` – optional from address for outgoing reports.
- `DEBUGER_MAIL_IN_CONSOLE` (default `true`) – send emails for exceptions thrown from CLI (queue workers, scheduler, artisan, ...).
- `DEBUGER_REFERENCE_PREFIX` – prefix for the generated reference id.

Note: the config also supports the common `DEBUGGER_*` env var spelling as a fallback.

## Behavior

- For 5xx errors, users see a clean HTML page (or a minimal JSON payload for API requests) with no technical details, plus an incident reference.
- A detailed email is sent for reportable 5xx exceptions containing the message, location, stack trace, and request context (headers and sanitized input fields).
- Exceptions below 500 (e.g., 404/422) fall back to Laravel's normal handling.
- The package replaces Laravel's exception handler binding; set `DEBUGER_ENABLED=false` to revert to the default behavior without uninstalling.

## Notes

- Input fields listed in `hidden_fields` are stripped from the email to avoid leaking secrets.
- Email failures are logged but will not break the response to the user.
