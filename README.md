# Invisible reCAPTCHA for Laravel

[![Packagist Version](https://img.shields.io/packagist/v/oriceon/invisible-recaptcha.svg)](https://packagist.org/packages/oriceon/invisible-recaptcha)
[![Total Downloads](https://img.shields.io/packagist/dt/oriceon/invisible-recaptcha.svg)](https://packagist.org/packages/oriceon/invisible-recaptcha)
[![PHP](https://img.shields.io/badge/php-%5E8.5-8892BF.svg)](https://www.php.net)
[![Laravel](https://img.shields.io/badge/laravel-%5E12.0-FF2D20.svg)](https://laravel.com)
[![License](https://img.shields.io/packagist/l/oriceon/invisible-recaptcha.svg)](LICENSE.md)

Google **reCAPTCHA v3** integration for **Laravel 12** built with **PHP 8.5**.

> **reCAPTCHA v3** works entirely in the background — no checkbox, no challenge, no user interaction required. It returns a **score from 0.0 (bot) to 1.0 (human)** and an **action name** that you verify server-side. Only the small `.grecaptcha-badge` appears on the page.

---

## Requirements

| Dependency | Version |
|---|---|
| PHP | `^8.5` |
| Laravel | `^12.0` |
| Guzzle | `^7.9` |

---

## Installation

```bash
composer require oriceon/invisible-recaptcha
```

Laravel auto-discovers the service provider via package discovery. No manual registration needed.

---

## Configuration

### 1. Publish the config file

```bash
php artisan vendor:publish --provider="Oriceon\InvisibleReCaptcha\InvisibleReCaptchaServiceProvider"
```

This publishes `config/captcha.php` to your application.

### 2. Register keys on Google

Go to [Google reCAPTCHA Admin](https://www.google.com/recaptcha/admin) and create a **reCAPTCHA v3** key pair. Then add them to your `.env`:

```env
# Required
INVISIBLE_RECAPTCHA_SITEKEY=your_site_key_here
INVISIBLE_RECAPTCHA_SECRETKEY=your_secret_key_here

# Optional (defaults shown)
INVISIBLE_RECAPTCHA_BADGEHIDE=false
INVISIBLE_RECAPTCHA_SCORE=0.5
INVISIBLE_RECAPTCHA_ACTION=submit
INVISIBLE_RECAPTCHA_TIMEOUT=5
INVISIBLE_RECAPTCHA_DEBUG=false
INVISIBLE_RECAPTCHA_ENABLED=true
```

### 3. Config reference (`config/captcha.php`)

```php
return [
    'siteKey'   => env('INVISIBLE_RECAPTCHA_SITEKEY'),
    'secretKey' => env('INVISIBLE_RECAPTCHA_SECRETKEY'),

    'options' => [
        // Hide the reCAPTCHA badge (.grecaptcha-badge) using visibility:hidden.
        // Per Google ToS, if hidden you must show "Protected by reCAPTCHA" text elsewhere.
        'hideBadge' => env('INVISIBLE_RECAPTCHA_BADGEHIDE', false),

        // Minimum score to accept (0.0 = bot, 1.0 = human).
        // Google recommends starting at 0.5 and tuning based on your traffic.
        'scoreThreshold' => env('INVISIBLE_RECAPTCHA_SCORE', 0.5),

        // Action name passed to grecaptcha.execute(key, {action}).
        // Verified server-side to prevent token re-use across different forms.
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
```

---

## How reCAPTCHA v3 Works

1. The page loads `api.js?render=SITE_KEY` (async defer).
2. On form submit, JavaScript calls `grecaptcha.execute(siteKey, {action})` which returns a **token** as a Promise.
3. The token is injected into a hidden `<input name="g-recaptcha-response">` and the form is submitted.
4. Your server sends the token to Google's verify API.
5. Google responds with `success`, `score` (0.0–1.0), and `action`.
6. You accept or reject the submission based on the score and action.

---

## Usage

### Important rules

- The captcha **must be inside a `<form>` element**.
- The form must have **exactly one** `<button type="submit">` or `<input type="submit">`.
- Each form should use a **unique action name** (e.g. `'login'`, `'contact'`, `'signup'`) to prevent token re-use.

---

### All-in-one render (recommended)

Renders the polyfill script, the hidden input, and the footer JS in a single call.

```blade
<form method="POST" action="/contact">
    @csrf

    {{-- your form fields --}}

    <button type="submit">Send</button>

    @captcha
</form>
```

With a language and a CSP nonce:

```blade
@captcha('ro', 'your-csp-nonce')
```

Using the service directly:

```php
{!! app('captcha')->render() !!}
{!! app('captcha')->render('ro') !!}
{!! app('captcha')->render('ro', $nonce) !!}
```

---

### Render in separate parts (Vue / React / SPA)

When using a JS framework that does not allow `<script>` tags inside component templates, render each part independently.

#### Polyfill — place in `<head>`

```blade
@captchaPolyfill
```

```php
{!! app('captcha')->renderPolyfill() !!}
```

#### Hidden input — place inside `<form>`

```blade
@captchaHTML
```

```php
{!! app('captcha')->renderCaptchaHTML() !!}
```

#### Footer scripts — place before `</body>`

```blade
@captchaScripts
@captchaScripts('ro')
@captchaScripts('ro', $nonce)
```

```php
{!! app('captcha')->renderFooterJS() !!}
{!! app('captcha')->renderFooterJS('ro') !!}
{!! app('captcha')->renderFooterJS('ro', $nonce) !!}
```

---

### Full Blade template example

```blade
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Contact</title>

    @captchaPolyfill
</head>
<body>

<form method="POST" action="/contact">
    @csrf

    <input type="text" name="name" placeholder="Nume">
    <input type="email" name="email" placeholder="Email">
    <textarea name="message"></textarea>

    @captchaHTML

    <button type="submit">Trimite</button>
</form>

@captchaScripts('ro')

</body>
</html>
```

---

### Per-form action names

Use a distinct `action` name for each form to allow score analysis per form type and prevent token re-use:

```php
// In a controller or middleware, override the action before rendering:
app('captcha')->setOption('action', 'login');

// Or boot a separate instance for each action:
$loginCaptcha  = new InvisibleReCaptcha($siteKey, $secretKey, ['action' => 'login']);
$signupCaptcha = new InvisibleReCaptcha($siteKey, $secretKey, ['action' => 'signup']);
```

---

### Validation

Add the `captcha` rule to your validation array. The rule automatically verifies the `g-recaptcha-response` token, checking Google's `success`, `score >= threshold`, and `action` match.

```php
// In a FormRequest:
public function rules(): array
{
    return [
        'name'                 => 'required|string|max:255',
        'email'                => 'required|email',
        'g-recaptcha-response' => 'required|captcha',
    ];
}
```

```php
// Or inline in a controller:
$request->validate([
    'g-recaptcha-response' => 'required|captcha',
]);
```

Custom error message in `lang/ro/validation.php`:

```php
'captcha' => 'Verificarea reCAPTCHA a eșuat. Încearcă din nou.',
```

---

### Manual verification

If you need to verify the captcha response manually (e.g. in an API endpoint):

```php
use Oriceon\InvisibleReCaptcha\InvisibleReCaptcha;

class ContactController extends Controller
{
    public function store(Request $request, InvisibleReCaptcha $captcha): RedirectResponse
    {
        // From a Symfony / Laravel Request object
        if (! $captcha->verifyRequest($request)) {
            abort(422, 'reCAPTCHA verification failed.');
        }

        // Or manually with token + IP
        $passed = $captcha->verifyResponse(
            $request->input('g-recaptcha-response'),
            $request->ip(),
        );
    }
}
```

`verifyResponse()` returns `true` only when all three conditions pass:
- `success === true`
- `score >= scoreThreshold` (default `0.5`)
- `action === configured action` (prevents token re-use across forms)

---

### Adjusting the score threshold

```php
// Accept only highly confident humans (strict)
app('captcha')->setOption('scoreThreshold', 0.7);

// Be more lenient (permissive)
app('captcha')->setOption('scoreThreshold', 0.3);
```

---

### Custom JS hooks

The package exposes two JavaScript hooks you can define **before** the captcha loads:

```javascript
// Called before form submission — return false to cancel execution
function _beforeSubmit(event) {
    // validate something, return true to proceed or false to stop
    return true;
}

// Called after Google confirms the token and right before form.submit()
function _submitEvent() {
    console.log('Form is being submitted!');
}
```

---

## Architecture (PHP 8.5)

This package is built exclusively with PHP 8.5 features.

### `Data/CaptchaOptions` — `readonly class` + `clone with`

An immutable value-object that holds all configuration. Mutation returns a new instance via PHP 8.5 `clone with`:

```php
readonly class CaptchaOptions
{
    public function __construct(
        public bool   $enabled        = true,
        public bool   $hideBadge      = false,
        public bool   $debug          = false,
        public int    $timeout        = 5,
        public float  $scoreThreshold = 0.5,
        public string $action         = 'submit',
    ) {}

    public static function fromArray(array $options): self { ... }

    // PHP 8.5 — clone with
    public function withScoreThreshold(float $scoreThreshold): self
    {
        return clone($this, scoreThreshold: $scoreThreshold);
    }

    public function withAction(string $action): self
    {
        return clone($this, action: $action);
    }
}
```

### `InvisibleReCaptcha` — pipe operator + `#[\NoDiscard]`

```php
// PHP 8.5 — pipe operator |>
// getCaptchaJs: appends ?render=SITE_KEY (required for v3)
public function getCaptchaJs(?string $lang = null): ?string
{
    return static::API_URI . '?render=' . $this->siteKey
        |> fn(string $url) => $lang ? $url . '&hl=' . $lang : $url;
}

// render: combines polyfill + hidden input + footer JS
public function render(?string $lang = null, ?string $nonce = null): ?string
{
    return [$this->renderPolyfill(), $this->renderCaptchaHTML(), $this->renderFooterJS($lang, $nonce)]
        |> fn(array $parts) => array_filter($parts)
        |> fn(array $parts) => implode('', $parts);
}

// verifyResponse: checks success + score + action via pipe operator
#[\NoDiscard('Always check the v3 captcha result — score and action must both pass')]
public function verifyResponse(string $response, string $clientIp): bool
{
    return ['secret' => $this->secretKey, 'remoteip' => $clientIp, 'response' => $response]
        |> fn(array $params) => $this->sendVerifyRequest($params)
        |> fn(array $result)  => $result['success'] === true
            && ($result['score']  ?? 0.0) >= $this->options->scoreThreshold
            && ($result['action'] ?? '')   === $this->options->action;
}
```

### PHP 8.5 Features Used

| PHP 8.5 Feature | Used in |
|---|---|
| Pipe operator `\|>` | `getCaptchaJs()`, `render()`, `renderDebug()`, `verifyResponse()` |
| `readonly class` | `CaptchaOptions` |
| `clone($this, prop: val)` | `CaptchaOptions::with*()` |
| `#[\NoDiscard]` / `#[\NoDiscard('msg')]` | All render & verify methods |
| `array_first()` / `array_last()` | `renderCaptchaHTML()` |
| Typed class constants `const string` / `const array` | `InvisibleReCaptcha` |
| Constructor property promotion + `readonly` | `InvisibleReCaptcha` |
| Named arguments | `verifyRequest()`, `sendVerifyRequest()` |
| `match` expression | `setOption()`, `getOption()` |

---

## Testing

This package uses **[Pest v3](https://pestphp.com)**.

```bash
composer install
./vendor/bin/pest
```

Test suites:

| File | Coverage |
|---|---|
| `tests/Unit/CaptchaOptionsTest.php` | `CaptchaOptions::fromArray()`, defaults, casting, all `with*` clone-with methods, chaining |
| `tests/Unit/InvisibleReCaptchaTest.php` | Constructor, options, `getCaptchaJs` (v3 `?render=` URL), render methods, footer JS (ready/execute), verify (score boundary, action mismatch, success=false) |
| `tests/Unit/BladeDirectiveTest.php` | All 4 Blade directives: `@captcha`, `@captchaPolyfill`, `@captchaHTML`, `@captchaScripts` |

---

## Blade Directives Reference

| Directive | Equivalent |
|---|---|
| `@captcha` | `app('captcha')->renderCaptcha()` |
| `@captcha('en')` | `app('captcha')->renderCaptcha('en')` |
| `@captcha('en', $nonce)` | `app('captcha')->renderCaptcha('en', $nonce)` |
| `@captchaPolyfill` | `app('captcha')->renderPolyfill()` |
| `@captchaHTML` | `app('captcha')->renderCaptchaHTML()` |
| `@captchaScripts` | `app('captcha')->renderFooterJS()` |
| `@captchaScripts('en')` | `app('captcha')->renderFooterJS('en')` |
| `@captchaScripts('en', $nonce)` | `app('captcha')->renderFooterJS('en', $nonce)` |

---

## Environment Variables Reference

| Variable | Default | Description |
|---|---|---|
| `INVISIBLE_RECAPTCHA_SITEKEY` | — | Google reCAPTCHA v3 site key (**required**) |
| `INVISIBLE_RECAPTCHA_SECRETKEY` | — | Google reCAPTCHA v3 secret key (**required**) |
| `INVISIBLE_RECAPTCHA_BADGEHIDE` | `false` | Hide the `.grecaptcha-badge` via `visibility:hidden` |
| `INVISIBLE_RECAPTCHA_SCORE` | `0.5` | Minimum score to accept (0.0 = bot, 1.0 = human) |
| `INVISIBLE_RECAPTCHA_ACTION` | `submit` | Action name for token scoping (e.g. `login`, `signup`) |
| `INVISIBLE_RECAPTCHA_TIMEOUT` | `5` | Guzzle HTTP timeout in seconds |
| `INVISIBLE_RECAPTCHA_DEBUG` | `false` | Log element binding status to browser console |
| `INVISIBLE_RECAPTCHA_ENABLED` | `true` | Set `false` to disable captcha (useful in tests) |

---

## Disabling in Tests

Set `INVISIBLE_RECAPTCHA_ENABLED=false` in your `.env.testing` to skip verification entirely during tests — `verifyResponse()` will return `true` automatically.

```env
# .env.testing
INVISIBLE_RECAPTCHA_ENABLED=false
```

---

## Credits

- [anhskohbo](https://github.com/anhskohbo) — original `no-captcha` package
- [albertcht](https://github.com/albertcht) — forked `invisible-recaptcha` package
- [Valentin Ivașcu](https://www.valentinivascu.ro)
- [Contributors](https://github.com/oriceon/invisible-recaptcha/graphs/contributors)

---

## License

MIT — see [LICENSE.md](LICENSE.md).
