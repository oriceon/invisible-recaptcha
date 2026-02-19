# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [4.0.0] — 2026-02-19

### Changed — reCAPTCHA v2 → v3 (breaking)

This release migrates the entire package from **reCAPTCHA v2 Invisible** to **reCAPTCHA v3**.
reCAPTCHA v3 is score-based and requires no user interaction whatsoever.

#### Script URL

- **Before (v2):** `https://www.google.com/recaptcha/api.js`
- **After (v3):** `https://www.google.com/recaptcha/api.js?render=SITE_KEY`

The `?render=SITE_KEY` parameter is mandatory for v3 and tells Google which site key to use on load.

#### HTML output

- **Before (v2):** Rendered a `<div class="g-recaptcha" data-sitekey="..." data-size="invisible" data-badge="..." data-callback="...">` widget div.
- **After (v3):** Renders only `<input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">`. No widget div. No `data-*` attributes.

#### JavaScript

- **Before (v2):** Used `grecaptcha.execute()` (no arguments) after widget render via callback.
- **After (v3):** Uses `grecaptcha.ready(fn)` + `grecaptcha.execute(SITE_KEY, {action: 'ACTION_NAME'})` which returns a Promise resolving to a token.

#### Badge

- **Before (v2):** Badge positioned via `data-badge="bottomright|bottomleft|inline"` on the widget div.
- **After (v3):** Badge is `.grecaptcha-badge` injected by the script. Hidden via `visibility: hidden` CSS (not `display: none`, per Google ToS).

#### Server-side verification

- **Before (v2):** Checked `success === true` only.
- **After (v3):** Checks all three:
  1. `success === true`
  2. `score >= scoreThreshold` (default `0.5`; 0.0 = bot, 1.0 = human)
  3. `action === configured action` (prevents token re-use across different forms)

### Added

- `scoreThreshold` option (float, default `0.5`) — minimum score to accept a submission.
- `action` option (string, default `'submit'`) — action name scoped per form, verified server-side.
- `INVISIBLE_RECAPTCHA_SCORE` env variable for configuring the score threshold.
- `INVISIBLE_RECAPTCHA_ACTION` env variable for configuring the default action name.
- Score boundary tests: score exactly at threshold passes; score just below fails.
- Action mismatch test: token from a different action is rejected.

### Removed

- `BadgePosition` backed enum (v2-only concept, not applicable to v3).
- `dataBadge` option and `INVISIBLE_RECAPTCHA_DATABADGE` env variable.
- v2 widget div rendering (`data-size`, `data-badge`, `data-callback` attributes).

### Migration guide from v3.x

1. **Re-register your keys** — go to [Google reCAPTCHA Admin](https://www.google.com/recaptcha/admin) and create a new **reCAPTCHA v3** key pair (not v2 invisible). Replace your old keys in `.env`.

2. **Update `.env`:**

   ```env
   # Remove:
   INVISIBLE_RECAPTCHA_DATABADGE=bottomright

   # Add:
   INVISIBLE_RECAPTCHA_SCORE=0.5
   INVISIBLE_RECAPTCHA_ACTION=submit
   ```

3. **Re-publish config** (if you published it previously):

   ```bash
   php artisan vendor:publish --provider="Oriceon\InvisibleReCaptcha\InvisibleReCaptchaServiceProvider" --force
   ```

4. **Blade templates** — no changes needed. `@captcha`, `@captchaHTML`, `@captchaScripts`, `@captchaPolyfill` all work the same way.

5. **Validation** — no changes needed. The `captcha` rule still applies to `g-recaptcha-response`.

6. **Per-form actions** — optionally set distinct action names for each form to enable per-action score analysis in the Google reCAPTCHA dashboard and prevent token re-use:

   ```php
   app('captcha')->setOption('action', 'login');
   ```

---

## [3.0.0] — 2025-01-01

### Added — PHP 8.5 + Laravel 12 rewrite

- **PHP 8.5 minimum** — requires PHP `^8.5`.
- **Laravel 12 only** — dropped support for Laravel 10 and 11 (`illuminate/* ^12.0`).
- **Pipe operator `|>`** — used in `getCaptchaJs()`, `render()`, `renderDebug()`, `verifyResponse()`.
- **`#[\NoDiscard]`** attribute on all render and verify methods — emits a compile-time warning if return value is silently discarded.
- **`array_first()` / `array_last()`** — used in `renderCaptchaHTML()` assertions.
- **`readonly class CaptchaOptions`** — immutable value-object replacing the raw options array.
- **`clone($this, prop: val)` (clone with)** — `CaptchaOptions::with*()` methods return new instances.
- **`BadgePosition` backed enum** — replaces raw `'bottomright'|'bottomleft'|'inline'` strings.
- **Typed class constants** — `const string API_URI`, `const string VERIFY_URI`, `const string POLYFILL_URI`, `const array DEBUG_ELEMENTS`.
- **Constructor property promotion + `readonly`** in `InvisibleReCaptcha`.
- **Named arguments** in `verifyRequest()` and `sendVerifyRequest()`.
- **`match` expression** in `setOption()` and `getOption()`.
- `getOptions()` now returns a `CaptchaOptions` instance instead of a raw array.
- Replaced PHPUnit with **Pest v3** + `pest-plugin-arch`.
- Namespace corrected to `Oriceon\InvisibleReCaptcha` (was `OriceOn`).

---

## [2.x] — Legacy

Previous versions supported Laravel 10/11 and PHP 8.0–8.4 with reCAPTCHA v2 Invisible.
See [git history](https://github.com/oriceon/invisible-recaptcha/commits/main) for details.
