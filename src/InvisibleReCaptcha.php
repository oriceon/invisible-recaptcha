<?php

namespace Oriceon\InvisibleReCaptcha;

use GuzzleHttp\Client;
use Oriceon\InvisibleReCaptcha\Data\CaptchaOptions;
use Symfony\Component\HttpFoundation\Request;

/**
 * Google reCAPTCHA v3 integration for Laravel 12.
 *
 * v3 differences from v2:
 *  - Script URL includes ?render=SITE_KEY
 *  - No widget div — only a hidden <input> in the form
 *  - JS uses grecaptcha.ready() + grecaptcha.execute(key, {action}) → Promise<token>
 *  - Verify response contains score (0.0–1.0) and action — both are validated
 *  - Badge is .grecaptcha-badge (visibility:hidden to hide, per Google ToS)
 */
class InvisibleReCaptcha
{
    // PHP 8.3+ typed class constants
    const string API_URI      = 'https://www.google.com/recaptcha/api.js';
    const string VERIFY_URI   = 'https://www.google.com/recaptcha/api/siteverify';
    const string POLYFILL_URI = 'https://cdnjs.cloudflare.com/polyfill/v3/polyfill.js';
    const array  DEBUG_ELEMENTS = ['grecaptcha', '_form', '_captchaSubmit'];

    private CaptchaOptions $options;
    private Client         $client;

    public function __construct(
        private readonly string $siteKey,
        private readonly string $secretKey,
        array $rawOptions = [],
    ) {
        $this->options = CaptchaOptions::fromArray($rawOptions);
        $this->client  = new Client(['timeout' => $this->options->timeout]);
    }

    // ─── JS URLs ──────────────────────────────────────────────────────────────

    /**
     * reCAPTCHA v3 — script URL must include ?render=SITE_KEY.
     * Optional &hl=lang for localisation.
     */
    #[\NoDiscard]
    public function getCaptchaJs(?string $lang = null): ?string
    {
        if (! $this->options->enabled) {
            return null;
        }

        // PHP 8.5 — pipe operator |>
        return static::API_URI . '?render=' . $this->siteKey
            |> fn(string $url) => $lang ? $url . '&hl=' . $lang : $url;
    }

    #[\NoDiscard]
    public function getPolyfillJs(): ?string
    {
        return $this->options->enabled ? static::POLYFILL_URI : null;
    }

    // ─── Render ───────────────────────────────────────────────────────────────

    #[\NoDiscard]
    public function render(?string $lang = null, ?string $nonce = null): ?string
    {
        if (! $this->options->enabled) {
            return null;
        }

        // PHP 8.5 — pipe operator |>
        return [$this->renderPolyfill(), $this->renderCaptchaHTML(), $this->renderFooterJS($lang, $nonce)]
            |> fn(array $parts) => array_filter($parts)
            |> fn(array $parts) => implode('', $parts);
    }

    #[\NoDiscard]
    public function renderCaptcha(?string $lang = null, ?string $nonce = null): ?string
    {
        return $this->options->enabled ? $this->render($lang, $nonce) : null;
    }

    #[\NoDiscard]
    public function renderPolyfill(): ?string
    {
        return $this->options->enabled
            ? '<script src="' . $this->getPolyfillJs() . '"></script>' . PHP_EOL
            : null;
    }

    /**
     * reCAPTCHA v3 — no widget div, just a hidden input to receive the token.
     * The JS will inject the token here before submitting the form.
     */
    #[\NoDiscard]
    public function renderCaptchaHTML(): ?string
    {
        if (! $this->options->enabled) {
            return null;
        }

        $parts = [
            '<input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">' . PHP_EOL,
        ];

        // v3 badge is .grecaptcha-badge — use visibility:hidden (not display:none)
        // so Google can still render it; this is acceptable per ToS when
        // reCAPTCHA branding is shown elsewhere (e.g. in a disclaimer).
        if ($this->options->hideBadge) {
            $parts[] = '<style>.grecaptcha-badge { visibility: hidden; }</style>' . PHP_EOL;
        }

        // PHP 8.5 — array_first() / array_last()
        assert(str_contains((string) array_first($parts), 'g-recaptcha-response'));
        assert(str_contains((string) array_last($parts), 'grecaptcha') || count($parts) === 1);

        return implode('', $parts);
    }

    /**
     * reCAPTCHA v3 footer JS:
     *  1. Loads api.js?render=SITE_KEY (async defer)
     *  2. On form submit: grecaptcha.ready() → execute(key, {action}) → inject token → submit
     */
    #[\NoDiscard]
    public function renderFooterJS(?string $lang = null, ?string $nonce = null): ?string
    {
        if (! $this->options->enabled) {
            return null;
        }

        $nonceAttr = $nonce ? ' nonce="' . htmlspecialchars($nonce, ENT_QUOTES) . '"' : '';

        // PHP 8.5 — pipe operator to build debug JS
        $debugJs = $this->options->debug ? $this->renderDebug() : '';

        $siteKey = $this->siteKey;
        $action  = $this->options->action;

        return implode('', [
            // 1. Load reCAPTCHA v3 script with site key
            '<script src="' . $this->getCaptchaJs($lang) . '" async defer' . $nonceAttr . '></script>' . PHP_EOL,
            // 2. Inline event listener
            '<script' . $nonceAttr . '>',
            "window.addEventListener('load',function(){",
            "var _form=document.querySelector('#g-recaptcha-response').closest('form');",
            "var _captchaSubmit=_form.querySelector('[type=submit]');",
            'var _execute=true;',
            "_form.addEventListener('submit',function(e){",
            'e.preventDefault();',
            "if(typeof _beforeSubmit==='function'){_execute=_beforeSubmit(e);}",
            'if(_execute){',
            'grecaptcha.ready(function(){',
            "grecaptcha.execute('{$siteKey}',{action:'{$action}'}).then(function(token){",
            "document.getElementById('g-recaptcha-response').value=token;",
            "if(typeof _submitEvent==='function'){_submitEvent();}else{_form.submit();}",
            '});',  // end .then
            '});',  // end grecaptcha.ready
            '}',    // end if(_execute)
            '});',  // end submit listener
            $debugJs,
            '});',  // end load listener
            '</script>' . PHP_EOL,
        ]);
    }

    #[\NoDiscard]
    public function renderDebug(): ?string
    {
        if (! $this->options->enabled) {
            return null;
        }

        // PHP 8.5 — pipe operator chaining array operations
        return static::DEBUG_ELEMENTS
            |> fn(array $els) => array_map(
                fn(string $el) => $this->consoleLog('"[reCAPTCHA v3] Binding check: ' . $el . '"')
                    . $this->consoleLog("typeof {$el}!=='undefined'"),
                $els,
            )
            |> fn(array $lines) => implode('', $lines);
    }

    public function consoleLog(string $string): string
    {
        return "console.log({$string});";
    }

    // ─── Verification ─────────────────────────────────────────────────────────

    /**
     * reCAPTCHA v3 verification:
     *  - success must be true
     *  - score must be >= scoreThreshold (default 0.5; 1.0 = human, 0.0 = bot)
     *  - action must match the configured action (prevents token re-use across actions)
     */
    #[\NoDiscard('Always check the v3 captcha result — score and action must both pass')]
    public function verifyResponse(string $response, string $clientIp): bool
    {
        if (! $this->options->enabled) {
            return true;
        }

        if (empty($response)) {
            return false;
        }

        // PHP 8.5 — pipe operator for verification flow
        return ['secret' => $this->secretKey, 'remoteip' => $clientIp, 'response' => $response]
            |> fn(array $params) => $this->sendVerifyRequest($params)
            |> fn(array $result)  => $result['success'] === true
                && ($result['score']  ?? 0.0) >= $this->options->scoreThreshold
                && ($result['action'] ?? '')   === $this->options->action;
    }

    #[\NoDiscard]
    public function verifyRequest(Request $request): bool
    {
        return $this->verifyResponse(
            response: (string) $request->request->get('g-recaptcha-response'),
            clientIp: (string) $request->getClientIp(),
        );
    }

    protected function sendVerifyRequest(array $query = []): array
    {
        $response = $this->client->post(static::VERIFY_URI, ['form_params' => $query]);

        return json_decode((string) $response->getBody(), associative: true) ?? [];
    }

    // ─── Getters ──────────────────────────────────────────────────────────────

    public function getSiteKey(): string   { return $this->siteKey; }
    public function getSecretKey(): string { return $this->secretKey; }
    public function getOptions(): CaptchaOptions { return $this->options; }
    public function getClient(): Client    { return $this->client; }

    // ─── Setters (delegate to CaptchaOptions clone-with) ─────────────────────

    public function setOptions(array $options): void
    {
        $this->options = CaptchaOptions::fromArray($options);
    }

    public function setOption(string $key, mixed $value): void
    {
        $this->options = match ($key) {
            'enabled'        => $this->options->withEnabled((bool)   $value),
            'hideBadge'      => $this->options->withHideBadge((bool)   $value),
            'debug'          => $this->options->withDebug((bool)   $value),
            'timeout'        => $this->options->withTimeout((int)    $value),
            'scoreThreshold' => $this->options->withScoreThreshold((float)  $value),
            'action'         => $this->options->withAction((string) $value),
            default          => $this->options,
        };
    }

    public function getOption(string $key, mixed $default = null): mixed
    {
        return match ($key) {
            'enabled'        => $this->options->enabled,
            'hideBadge'      => $this->options->hideBadge,
            'debug'          => $this->options->debug,
            'timeout'        => $this->options->timeout,
            'scoreThreshold' => $this->options->scoreThreshold,
            'action'         => $this->options->action,
            default          => $default,
        };
    }

    public function setClient(Client $client): void
    {
        $this->client = $client;
    }
}
