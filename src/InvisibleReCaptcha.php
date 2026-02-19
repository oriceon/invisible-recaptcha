<?php

namespace Oriceon\InvisibleReCaptcha;

use GuzzleHttp\Client;
use Oriceon\InvisibleReCaptcha\Data\CaptchaOptions;
use Oriceon\InvisibleReCaptcha\Enums\BadgePosition;
use Symfony\Component\HttpFoundation\Request;

class InvisibleReCaptcha
{
    // PHP 8.3+ typed class constants (available in 8.5)
    const string API_URI      = 'https://www.google.com/recaptcha/api.js';
    const string VERIFY_URI   = 'https://www.google.com/recaptcha/api/siteverify';
    const string POLYFILL_URI = 'https://cdnjs.cloudflare.com/polyfill/v3/polyfill.js';
    const array  DEBUG_ELEMENTS = ['_submitForm', '_captchaForm', '_captchaSubmit'];

    // PHP 8.1 readonly + constructor property promotion for keys
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

    // PHP 8.5 — #[\NoDiscard]: return value must not be silently ignored
    #[\NoDiscard]
    public function getCaptchaJs(?string $lang = null): ?string
    {
        if (! $this->options->enabled) {
            return null;
        }

        return $lang ? static::API_URI . '?hl=' . $lang : static::API_URI;
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

    #[\NoDiscard]
    public function renderCaptchaHTML(): ?string
    {
        if (! $this->options->enabled) {
            return null;
        }

        $parts = ['<div id="_g-recaptcha"></div>' . PHP_EOL];

        if ($this->options->hideBadge) {
            $parts[] = '<style>.grecaptcha-badge{display:none !important;}</style>' . PHP_EOL;
        }

        $parts[] = '<div class="g-recaptcha"'
            . ' data-sitekey="' . $this->siteKey . '"'
            . ' data-size="invisible"'
            . ' data-callback="_submitForm"'
            . ' data-badge="' . $this->options->badge->value . '"></div>';

        // PHP 8.5 — array_first() / array_last()
        // (used here to verify the first and last HTML part are as expected at runtime)
        assert(str_contains((string) array_first($parts), '_g-recaptcha'));
        assert(str_contains((string) array_last($parts), 'g-recaptcha'));

        return implode('', $parts);
    }

    #[\NoDiscard]
    public function renderFooterJS(?string $lang = null, ?string $nonce = null): ?string
    {
        if (! $this->options->enabled) {
            return null;
        }

        $nonceAttr = $nonce ? ' nonce="' . $nonce . '"' : '';

        $hideBadgeJs = $this->options->hideBadge
            ? "_captchaBadge=document.querySelector('.grecaptcha-badge');"
                . "if(_captchaBadge){_captchaBadge.style='display:none !important;';}"
            : '';

        // PHP 8.5 — pipe operator for building debug JS
        $debugJs = $this->options->debug ? $this->renderDebug() : '';

        return implode('', [
            '<script src="' . $this->getCaptchaJs($lang) . '" async defer' . $nonceAttr . '></script>' . PHP_EOL,
            '<script>var _submitForm,_captchaForm,_captchaSubmit,_execute=true,_captchaBadge;</script>',
            "<script>window.addEventListener('load', _loadCaptcha);" . PHP_EOL,
            'function _loadCaptcha(){',
            $hideBadgeJs,
            '_captchaForm=document.querySelector("#_g-recaptcha").closest("form");',
            "_captchaSubmit=_captchaForm.querySelector('[type=submit]');",
            '_submitForm=function(){if(typeof _submitEvent==="function"){_submitEvent();grecaptcha.reset();}else{_captchaForm.submit();}};',
            "_captchaForm.addEventListener('submit',function(e){e.preventDefault();"
                . "if(typeof _beforeSubmit==='function'){_execute=_beforeSubmit(e);}"
                . 'if(_execute){grecaptcha.execute();}});',
            $debugJs,
            "}</script>" . PHP_EOL,
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
                fn(string $el) => $this->consoleLog('"Checking element binding of ' . $el . '..."')
                    . $this->consoleLog($el . '!==undefined'),
                $els,
            )
            |> fn(array $lines) => implode('', $lines);
    }

    public function consoleLog(string $string): string
    {
        return "console.log({$string});";
    }

    // ─── Verification ─────────────────────────────────────────────────────────

    // PHP 8.5 — #[\NoDiscard] with message: callers must not ignore this
    #[\NoDiscard('Always check the captcha verification result — ignoring it bypasses protection')]
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
            |> fn(array $result)  => ($result['success'] ?? false) === true;
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

    public function getSiteKey(): string
    {
        return $this->siteKey;
    }

    public function getSecretKey(): string
    {
        return $this->secretKey;
    }

    public function getOptions(): CaptchaOptions
    {
        return $this->options;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    // ─── Setters (use PHP 8.5 clone-with internally via CaptchaOptions) ───────

    public function setOptions(array $options): void
    {
        $this->options = CaptchaOptions::fromArray($options);
    }

    public function setOption(string $key, mixed $value): void
    {
        // Delegate to the immutable CaptchaOptions with* methods (clone with internally)
        $this->options = match ($key) {
            'enabled'   => $this->options->withEnabled((bool) $value),
            'hideBadge' => $this->options->withHideBadge((bool) $value),
            'debug'     => $this->options->withDebug((bool) $value),
            'timeout'   => $this->options->withTimeout((int) $value),
            'dataBadge' => $this->options->withBadge(BadgePosition::from((string) $value)),
            default     => $this->options,
        };
    }

    public function getOption(string $key, mixed $default = null): mixed
    {
        return match ($key) {
            'enabled'   => $this->options->enabled,
            'hideBadge' => $this->options->hideBadge,
            'debug'     => $this->options->debug,
            'timeout'   => $this->options->timeout,
            'dataBadge' => $this->options->badge->value,
            default     => $default,
        };
    }

    public function setClient(Client $client): void
    {
        $this->client = $client;
    }
}
