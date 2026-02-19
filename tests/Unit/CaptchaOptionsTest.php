<?php

use Oriceon\InvisibleReCaptcha\Data\CaptchaOptions;

// ─── CaptchaOptions — construction ───────────────────────────────────────────

describe('CaptchaOptions::fromArray', function () {
    it('builds from a full options array', function () {
        $opts = CaptchaOptions::fromArray([
            'enabled'        => true,
            'hideBadge'      => false,
            'debug'          => false,
            'timeout'        => 5,
            'scoreThreshold' => 0.5,
            'action'         => 'submit',
        ]);

        expect($opts->enabled)->toBeTrue()
            ->and($opts->hideBadge)->toBeFalse()
            ->and($opts->debug)->toBeFalse()
            ->and($opts->timeout)->toBe(5)
            ->and($opts->scoreThreshold)->toBe(0.5)
            ->and($opts->action)->toBe('submit');
    });

    it('uses defaults for missing keys', function () {
        $opts = CaptchaOptions::fromArray([]);

        expect($opts->enabled)->toBeTrue()
            ->and($opts->hideBadge)->toBeFalse()
            ->and($opts->debug)->toBeFalse()
            ->and($opts->timeout)->toBe(5)
            ->and($opts->scoreThreshold)->toBe(0.5)
            ->and($opts->action)->toBe('submit');
    });

    it('casts timeout to int', function () {
        expect(CaptchaOptions::fromArray(['timeout' => '10'])->timeout)->toBe(10)->toBeInt();
    });

    it('casts scoreThreshold to float', function () {
        expect(CaptchaOptions::fromArray(['scoreThreshold' => '0.7'])->scoreThreshold)->toBe(0.7)->toBeFloat();
    });

    it('stores a custom action', function () {
        expect(CaptchaOptions::fromArray(['action' => 'login'])->action)->toBe('login');
    });
});

// ─── CaptchaOptions — PHP 8.5 clone with ─────────────────────────────────────

describe('CaptchaOptions clone-with methods', function () {
    it('withEnabled returns a new instance, leaving original unchanged', function () {
        $original = CaptchaOptions::fromArray([]);
        $updated  = $original->withEnabled(false);

        expect($updated->enabled)->toBeFalse()
            ->and($original->enabled)->toBeTrue()
            ->and($updated)->not->toBe($original);
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

    it('withScoreThreshold returns a new instance', function () {
        $original = CaptchaOptions::fromArray([]);
        $updated  = $original->withScoreThreshold(0.8);

        expect($updated->scoreThreshold)->toBe(0.8)
            ->and($original->scoreThreshold)->toBe(0.5);
    });

    it('withAction returns a new instance', function () {
        $original = CaptchaOptions::fromArray([]);
        $updated  = $original->withAction('login');

        expect($updated->action)->toBe('login')
            ->and($original->action)->toBe('submit');
    });

    it('chaining with* calls produces immutable instances each time', function () {
        $opts = CaptchaOptions::fromArray([])
            ->withEnabled(false)
            ->withScoreThreshold(0.9)
            ->withAction('signup')
            ->withTimeout(10);

        expect($opts->enabled)->toBeFalse()
            ->and($opts->scoreThreshold)->toBe(0.9)
            ->and($opts->action)->toBe('signup')
            ->and($opts->timeout)->toBe(10);
    });
});
