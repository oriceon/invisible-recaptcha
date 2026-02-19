<?php

use Oriceon\InvisibleReCaptcha\Data\CaptchaOptions;
use Oriceon\InvisibleReCaptcha\Enums\BadgePosition;

// ─── BadgePosition enum ───────────────────────────────────────────────────────

describe('BadgePosition enum', function () {
    it('has the correct string values', function () {
        expect(BadgePosition::BottomRight->value)->toBe('bottomright')
            ->and(BadgePosition::BottomLeft->value)->toBe('bottomleft')
            ->and(BadgePosition::Inline->value)->toBe('inline');
    });

    it('can be created from a string', function () {
        expect(BadgePosition::from('bottomright'))->toBe(BadgePosition::BottomRight)
            ->and(BadgePosition::from('bottomleft'))->toBe(BadgePosition::BottomLeft)
            ->and(BadgePosition::from('inline'))->toBe(BadgePosition::Inline);
    });

    it('returns null for unknown value via tryFrom', function () {
        expect(BadgePosition::tryFrom('unknown'))->toBeNull();
    });
});

// ─── CaptchaOptions — construction ───────────────────────────────────────────

describe('CaptchaOptions::fromArray', function () {
    it('builds from a full options array', function () {
        $opts = CaptchaOptions::fromArray([
            'enabled'   => true,
            'hideBadge' => false,
            'debug'     => false,
            'timeout'   => 5,
            'dataBadge' => 'bottomright',
        ]);

        expect($opts->enabled)->toBeTrue()
            ->and($opts->hideBadge)->toBeFalse()
            ->and($opts->debug)->toBeFalse()
            ->and($opts->timeout)->toBe(5)
            ->and($opts->badge)->toBe(BadgePosition::BottomRight);
    });

    it('uses defaults for missing keys', function () {
        $opts = CaptchaOptions::fromArray([]);

        expect($opts->enabled)->toBeTrue()
            ->and($opts->hideBadge)->toBeFalse()
            ->and($opts->debug)->toBeFalse()
            ->and($opts->timeout)->toBe(5)
            ->and($opts->badge)->toBe(BadgePosition::BottomRight);
    });

    it('casts timeout to int', function () {
        $opts = CaptchaOptions::fromArray(['timeout' => '10']);

        expect($opts->timeout)->toBe(10)->toBeInt();
    });

    it('maps dataBadge string to BadgePosition enum', function () {
        expect(CaptchaOptions::fromArray(['dataBadge' => 'inline'])->badge)
            ->toBe(BadgePosition::Inline);
    });
});

// ─── CaptchaOptions — PHP 8.5 clone with ─────────────────────────────────────

describe('CaptchaOptions clone-with methods', function () {
    it('withEnabled returns a new instance with updated enabled', function () {
        $original = CaptchaOptions::fromArray([]);
        $updated  = $original->withEnabled(false);

        expect($updated->enabled)->toBeFalse()
            ->and($original->enabled)->toBeTrue()    // original is unchanged
            ->and($updated)->not->toBe($original);   // different instance
    });

    it('withHideBadge returns a new instance', function () {
        $original = CaptchaOptions::fromArray([]);
        $updated  = $original->withHideBadge(true);

        expect($updated->hideBadge)->toBeTrue()
            ->and($original->hideBadge)->toBeFalse();
    });

    it('withDebug returns a new instance', function () {
        $original = CaptchaOptions::fromArray([]);
        $updated  = $original->withDebug(true);

        expect($updated->debug)->toBeTrue()
            ->and($original->debug)->toBeFalse();
    });

    it('withTimeout returns a new instance', function () {
        $original = CaptchaOptions::fromArray([]);
        $updated  = $original->withTimeout(30);

        expect($updated->timeout)->toBe(30)
            ->and($original->timeout)->toBe(5);
    });

    it('withBadge returns a new instance with the given BadgePosition', function () {
        $original = CaptchaOptions::fromArray([]);
        $updated  = $original->withBadge(BadgePosition::Inline);

        expect($updated->badge)->toBe(BadgePosition::Inline)
            ->and($original->badge)->toBe(BadgePosition::BottomRight);
    });

    it('chaining with* calls produces a new immutable instance each time', function () {
        $opts = CaptchaOptions::fromArray([])
            ->withEnabled(false)
            ->withTimeout(15)
            ->withBadge(BadgePosition::BottomLeft);

        expect($opts->enabled)->toBeFalse()
            ->and($opts->timeout)->toBe(15)
            ->and($opts->badge)->toBe(BadgePosition::BottomLeft);
    });
});
