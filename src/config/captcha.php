<?php

return [
    // Your reCAPTCHA v3 site key (public key)
    'siteKey' => env('INVISIBLE_RECAPTCHA_SITEKEY'),

    // Your reCAPTCHA v3 secret key (private key)
    'secretKey' => env('INVISIBLE_RECAPTCHA_SECRETKEY'),

    'options' => [
        // Hide the reCAPTCHA badge (.grecaptcha-badge).
        // Uses visibility:hidden (not display:none) per Google ToS.
        // If hidden, you must show "Protected by reCAPTCHA" text elsewhere.
        'hideBadge' => env('INVISIBLE_RECAPTCHA_BADGEHIDE', false),

        // Minimum score to accept (0.0 = bot, 1.0 = human).
        // Google recommends starting at 0.5 and adjusting based on your traffic.
        'scoreThreshold' => env('INVISIBLE_RECAPTCHA_SCORE', 0.5),

        // Action name passed to grecaptcha.execute(key, {action}).
        // Used to scope tokens — verified server-side to prevent token re-use.
        // Use distinct values per form: 'submit', 'login', 'signup', 'contact', etc.
        'action' => env('INVISIBLE_RECAPTCHA_ACTION', 'submit'),

        // Guzzle HTTP timeout in seconds for the verify API call
        'timeout' => env('INVISIBLE_RECAPTCHA_TIMEOUT', 5),

        // Log reCAPTCHA binding status to the browser console
        'debug' => env('INVISIBLE_RECAPTCHA_DEBUG', false),

        // Set false to bypass captcha completely (useful in automated tests)
        'enabled' => env('INVISIBLE_RECAPTCHA_ENABLED', true),
    ],
];
