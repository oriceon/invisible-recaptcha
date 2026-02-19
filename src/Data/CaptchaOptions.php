<?php

namespace Oriceon\InvisibleReCaptcha\Data;

use Oriceon\InvisibleReCaptcha\Enums\BadgePosition;

/**
 * Immutable value-object holding all reCAPTCHA configuration.
 * Uses PHP 8.5 readonly class + clone-with syntax.
 */
readonly class CaptchaOptions
{
    public function __construct(
        public bool          $enabled   = true,
        public bool          $hideBadge = false,
        public bool          $debug     = false,
        public int           $timeout   = 5,
        public BadgePosition $badge     = BadgePosition::BottomRight,
    ) {}

    // ─── Factory ──────────────────────────────────────────────────────────────

    public static function fromArray(array $options): self
    {
        return new self(
            enabled:   (bool)   ($options['enabled']   ?? true),
            hideBadge: (bool)   ($options['hideBadge'] ?? false),
            debug:     (bool)   ($options['debug']     ?? false),
            timeout:   (int)    ($options['timeout']   ?? 5),
            badge:     BadgePosition::from($options['dataBadge'] ?? 'bottomright'),
        );
    }

    // ─── PHP 8.5 — clone with ────────────────────────────────────────────────

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

    public function withBadge(BadgePosition $badge): self
    {
        return clone($this, badge: $badge);
    }
}
