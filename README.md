# Invisible reCAPTCHA for Laravel

[![Packagist Version](https://img.shields.io/packagist/v/oriceon/invisible-recaptcha.svg)](https://packagist.org/packages/oriceon/invisible-recaptcha)
[![Total Downloads](https://img.shields.io/packagist/dt/oriceon/invisible-recaptcha.svg)](https://packagist.org/packages/oriceon/invisible-recaptcha)
[![PHP](https://img.shields.io/badge/php-%5E8.5-8892BF.svg)](https://www.php.net)
[![Laravel](https://img.shields.io/badge/laravel-%5E12.0-FF2D20.svg)](https://laravel.com)
[![License](https://img.shields.io/packagist/l/oriceon/invisible-recaptcha.svg)](LICENSE.md)

Google Invisible reCAPTCHA v2 integration for **Laravel 12** built with **PHP 8.5**.

> **Invisible reCAPTCHA** works silently in the background — no "I'm not a robot" checkbox needed. Only a small badge appears at the bottom of the page to indicate protection.

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

### 2. Add keys to `.env`

Go to [Google reCAPTCHA Admin](https://www.google.com/recaptcha/admin) and create an **Invisible reCAPTCHA v2** key pair, then add them to your `.env`:

```env
# Required
INVISIBLE_RECAPTCHA_SITEKEY=your_site_key_here
INVISIBLE_RECAPTCHA_SECRETKEY=your_secret_key_here

# Optional (defaults shown)
INVISIBLE_RECAPTCHA_BADGEHIDE=false
INVISIBLE_RECAPTCHA_DATABADGE=bottomright
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
        // Hide the reCAPTCHA badge (not recommended by Google)
        'hideBadge' => env('INVISIBLE_RECAPTCHA_BADGEHIDE', false),

        // Badge position: 'bottomright' | 'bottomleft' | 'inline'
        'dataBadge' => env('INVISIBLE_RECAPTCHA_DATABADGE', 'bottomright'),

        // Guzzle HTTP timeout in seconds
        'timeout'   => env('INVISIBLE_RECAPTCHA_TIMEOUT', 5),

        // Show binding debug info in the browser console
        'debug'     => env('INVISIBLE_RECAPTCHA_DEBUG', false),

        // Set false to bypass captcha entirely (useful in testing)
        'enabled'   => env('INVISIBLE_RECAPTCHA_ENABLED', true),
    ],
];
```

---

## Usage

### Important rules

- The captcha **must be inside a `<form>` element**.
- The form must have **exactly one** `<button type="submit">` or `<input type="submit">`.
- The submit button **must** have `type="submit"`.

---

### All-in-one render (recommended)

Renders the polyfill script, the reCAPTCHA HTML, and the footer JS in one call.

```blade
<form method="POST" action="/contact">
    @csrf

    {{-- your form fields --}}

    <button type="submit">Send</button>

    @captcha
</form>
```

Or with a language and a CSP nonce:

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

#### HTML widget — place inside `<form>`

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

### Validation

Add the `captcha` rule to your validation array. The rule automatically verifies the `g-recaptcha-response` token against Google's API.

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

### `Enums/BadgePosition`

```php
enum BadgePosition: string
{
    case BottomRight = 'bottomright';
    case BottomLeft  = 'bottomleft';
    case Inline      = 'inline';
}
```

### `Data/CaptchaOptions` — `readonly class` + `clone with`

```php
readonly class CaptchaOptions
{
    public function __construct(
        public bool          $enabled   = true,
        public bool          $hideBadge = false,
        public bool          $debug     = false,
        public int           $timeout   = 5,
        public BadgePosition $badge     = BadgePosition::BottomRight,
    ) {}

    // PHP 8.5 — clone with
    public function withBadge(BadgePosition $badge): self
    {
        return clone($this, badge: $badge);
    }
}
```

### `InvisibleReCaptcha` — pipe operator + `#[\NoDiscard]`

```php
// PHP 8.5 — pipe operator |>
public function render(?string $lang = null, ?string $nonce = null): ?string
{
    return [$this->renderPolyfill(), $this->renderCaptchaHTML(), $this->renderFooterJS($lang, $nonce)]
        |> fn(array $parts) => array_filter($parts)
        |> fn(array $parts) => implode('', $parts);
}

// PHP 8.5 — #[\NoDiscard] prevents silent ignore of return value
#[\NoDiscard('Always check the captcha verification result')]
public function verifyResponse(string $response, string $clientIp): bool { ... }
```

| PHP 8.5 Feature | Used in |
|---|---|
| Pipe operator `\|>` | `render()`, `renderDebug()`, `verifyResponse()` |
| `readonly class` | `CaptchaOptions` |
| `clone($this, prop: val)` | `CaptchaOptions::with*()` |
| `#[\NoDiscard]` / `#[\NoDiscard('msg')]` | All render & verify methods |
| `array_first()` / `array_last()` | `renderCaptchaHTML()` |
| `enum` (backed) | `BadgePosition` |
| Typed class constants `const string` | `InvisibleReCaptcha` |
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
| `tests/Unit/CaptchaOptionsTest.php` | `BadgePosition` enum, `CaptchaOptions::fromArray()`, all `with*` clone-with methods |
| `tests/Unit/InvisibleReCaptchaTest.php` | Constructor, options, all render methods, verify methods (with Guzzle mock) |
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
| `INVISIBLE_RECAPTCHA_SITEKEY` | — | Google reCAPTCHA site key (**required**) |
| `INVISIBLE_RECAPTCHA_SECRETKEY` | — | Google reCAPTCHA secret key (**required**) |
| `INVISIBLE_RECAPTCHA_BADGEHIDE` | `false` | Hide the reCAPTCHA badge (not recommended) |
| `INVISIBLE_RECAPTCHA_DATABADGE` | `bottomright` | Badge position: `bottomright` \| `bottomleft` \| `inline` |
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

## Changelog

### v3.0.0
- **PHP 8.5 minimum** — pipe operator `|>`, `#[\NoDiscard]`, `array_first()` / `array_last()`, `clone with`
- **Laravel 12 only** — dropped support for Laravel 10 and 11
- New `BadgePosition` backed enum replaces raw strings
- New `CaptchaOptions` immutable `readonly` value-object with `clone with` wither methods
- Replaced PHPUnit with **Pest v3** + `pest-plugin-arch`
- `getOptions()` now returns a `CaptchaOptions` instance instead of a raw array
- All public render/verify methods annotated with `#[\NoDiscard]`
- Namespace corrected to `Oriceon\InvisibleReCaptcha`

---

## Credits

- [anhskohbo](https://github.com/anhskohbo) — original `no-captcha` package
- [albertcht](https://github.com/albertcht) — forked `invisible-recaptcha` package
- [Valentin Ivașcu](https://www.valentinivascu.ro)
- [Contributors](https://github.com/oriceon/invisible-recaptcha/graphs/contributors)

---

## License

MIT — see [LICENSE.md](LICENSE.md).
