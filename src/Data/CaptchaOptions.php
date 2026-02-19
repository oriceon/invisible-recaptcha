<?php

namespace Oriceon\InvisibleReCaptcha\Data;

/**
 * Immutable value-object holding all reCAPTCHA v3 configuration.
 * PHP 8.5: readonly class + clone-with syntax.
 */
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

    // ─── Factory ──────────────────────────────────────────────────────────────

    public static function fromArray(array $options): self
    {
        return new self(
            enabled:        (bool)   ($options['enabled']        ?? true),
            hideBadge:      (bool)   ($options['hideBadge']      ?? false),
            debug:          (bool)   ($options['debug']          ?? false),
            timeout:        (int)    ($options['timeout']        ?? 5),
            scoreThreshold: (float)  ($options['scoreThreshold'] ?? 0.5),
            action:         (string) ($options['action']         ?? 'submit'),
        );
    }

    // ─── PHP 8.5 — clone with ─────────────────────────────────────────────────

    public function withEnabled(bool $enabled): self
    {
        return clone($this, enabled: $enabled);
    }

    public function withHideBadge(bool $hideBadge): self
    {
        return clone($this, hideBadge: $hideBadge);
    }

    public function withDebug(bool $debug): self
    {
        return clone($this, debug: $debug);
    }

    public function withTimeout(int $timeout): self
    {
        return clone($this, timeout: $timeout);
    }

    public function withScoreThreshold(float $scoreThreshold): self
    {
        return clone($this, scoreThreshold: $scoreThreshold);
    }

    public function withAction(string $action): self
    {
        return clone($this, action: $action);
    }
}
