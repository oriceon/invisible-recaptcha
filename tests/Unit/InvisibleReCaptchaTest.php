<?php

use GuzzleHttp\Client;
use Oriceon\InvisibleReCaptcha\Data\CaptchaOptions;
use Oriceon\InvisibleReCaptcha\Enums\BadgePosition;
use Oriceon\InvisibleReCaptcha\InvisibleReCaptcha;
use Symfony\Component\HttpFoundation\Request;

// ─── Constructor & Getters ────────────────────────────────────────────────────

describe('constructor', function () {
    it('stores siteKey and secretKey as readonly properties', function () {
        $captcha = makeCaptcha();

        expect($captcha->getSiteKey())->toBe(SITE_KEY)
            ->and($captcha->getSecretKey())->toBe(SECRET_KEY);
    });

    it('initialises a Guzzle client', function () {
        expect(makeCaptcha()->getClient())->toBeInstanceOf(Client::class);
    });

    it('builds a CaptchaOptions instance from the raw array', function () {
        expect(makeCaptcha()->getOptions())->toBeInstanceOf(CaptchaOptions::class);
    });

    it('maps raw options to the CaptchaOptions value-object', function () {
        $opts = makeCaptcha()->getOptions();

        expect($opts->enabled)->toBeTrue()
            ->and($opts->hideBadge)->toBeFalse()
            ->and($opts->timeout)->toBe(5)
            ->and($opts->debug)->toBeFalse()
            ->and($opts->badge)->toBe(BadgePosition::BottomRight);
    });
});

// ─── setOption / getOption — use CaptchaOptions clone-with internally ─────────

describe('setOption / getOption', function () {
    it('returns the correct value for each known option key', function () {
        $captcha = makeCaptcha();

        expect($captcha->getOption('enabled'))->toBeTrue()
            ->and($captcha->getOption('hideBadge'))->toBeFalse()
            ->and($captcha->getOption('debug'))->toBeFalse()
            ->and($captcha->getOption('timeout'))->toBe(5)
            ->and($captcha->getOption('dataBadge'))->toBe('bottomright');
    });

    it('returns the default for an unknown key', function () {
        expect(makeCaptcha()->getOption('nonExistent', 'fallback'))->toBe('fallback');
    });

    it('updates enabled via setOption (uses clone-with internally)', function () {
        $captcha = makeCaptcha();
        $captcha->setOption('enabled', false);

        expect($captcha->getOption('enabled'))->toBeFalse();
    });

    it('updates debug via setOption', function () {
        $captcha = makeCaptcha();
        $captcha->setOption('debug', true);

        expect($captcha->getOption('debug'))->toBeTrue();
    });

    it('updates timeout via setOption', function () {
        $captcha = makeCaptcha();
        $captcha->setOption('timeout', 30);

        expect($captcha->getOption('timeout'))->toBe(30);
    });

    it('updates dataBadge via setOption and reflects in enum', function () {
        $captcha = makeCaptcha();
        $captcha->setOption('dataBadge', 'inline');

        expect($captcha->getOption('dataBadge'))->toBe('inline')
            ->and($captcha->getOptions()->badge)->toBe(BadgePosition::Inline);
    });
});

describe('setOptions', function () {
    it('replaces all options at once', function () {
        $captcha = makeCaptcha();
        $captcha->setOptions(['enabled' => false, 'hideBadge' => true, 'dataBadge' => 'inline', 'timeout' => 3, 'debug' => true]);

        $opts = $captcha->getOptions();

        expect($opts->enabled)->toBeFalse()
            ->and($opts->hideBadge)->toBeTrue()
            ->and($opts->badge)->toBe(BadgePosition::Inline)
            ->and($opts->timeout)->toBe(3)
            ->and($opts->debug)->toBeTrue();
    });
});

// ─── getCaptchaJs ─────────────────────────────────────────────────────────────

describe('getCaptchaJs', function () {
    it('returns the api url without lang', function () {
        expect(makeCaptcha()->getCaptchaJs())->toBe(InvisibleReCaptcha::API_URI);
    });

    it('appends hl param when lang is given', function () {
        expect(makeCaptcha()->getCaptchaJs('ro'))->toBe(InvisibleReCaptcha::API_URI . '?hl=ro');
    });

    it('returns null when captcha is disabled', function () {
        expect(makeCaptcha(['enabled' => false])->getCaptchaJs())->toBeNull();
    });
});

// ─── getPolyfillJs ────────────────────────────────────────────────────────────

describe('getPolyfillJs', function () {
    it('returns the polyfill cdn url', function () {
        expect(makeCaptcha()->getPolyfillJs())->toBe(InvisibleReCaptcha::POLYFILL_URI);
    });

    it('returns null when captcha is disabled', function () {
        expect(makeCaptcha(['enabled' => false])->getPolyfillJs())->toBeNull();
    });
});

// ─── renderPolyfill ───────────────────────────────────────────────────────────

describe('renderPolyfill', function () {
    it('renders a <script> tag containing the polyfill url', function () {
        expect(makeCaptcha()->renderPolyfill())
            ->toContain('<script')
            ->toContain(InvisibleReCaptcha::POLYFILL_URI);
    });

    it('returns null when disabled', function () {
        expect(makeCaptcha(['enabled' => false])->renderPolyfill())->toBeNull();
    });
});

// ─── renderCaptchaHTML ────────────────────────────────────────────────────────

describe('renderCaptchaHTML', function () {
    it('contains the g-recaptcha div with sitekey and badge from enum', function () {
        $html = makeCaptcha()->renderCaptchaHTML();

        expect($html)
            ->toContain('g-recaptcha')
            ->toContain(SITE_KEY)
            ->toContain('data-size="invisible"')
            ->toContain('data-callback="_submitForm"')
            ->toContain('data-badge="' . BadgePosition::BottomRight->value . '"');
    });

    it('contains the anchor div', function () {
        expect(makeCaptcha()->renderCaptchaHTML())->toContain('id="_g-recaptcha"');
    });

    it('injects CSS to hide badge when hideBadge is true', function () {
        expect(makeCaptcha(['hideBadge' => true])->renderCaptchaHTML())->toContain('display:none');
    });

    it('does not inject hide-badge CSS when hideBadge is false', function () {
        expect(makeCaptcha(['hideBadge' => false])->renderCaptchaHTML())->not->toContain('display:none');
    });

    it('uses the configured BadgePosition enum value in HTML', function () {
        $html = makeCaptcha(['dataBadge' => 'bottomleft'])->renderCaptchaHTML();

        expect($html)->toContain('data-badge="' . BadgePosition::BottomLeft->value . '"');
    });

    it('returns null when disabled', function () {
        expect(makeCaptcha(['enabled' => false])->renderCaptchaHTML())->toBeNull();
    });
});

// ─── renderFooterJS ───────────────────────────────────────────────────────────

describe('renderFooterJS', function () {
    it('includes the api script tag', function () {
        expect(makeCaptcha()->renderFooterJS())
            ->toContain('<script')
            ->toContain(InvisibleReCaptcha::API_URI);
    });

    it('appends hl param to api url when lang is provided', function () {
        expect(makeCaptcha()->renderFooterJS('ro'))->toContain('?hl=ro');
    });

    it('includes nonce attribute when nonce is given', function () {
        expect(makeCaptcha()->renderFooterJS('en', 'nonce-XYZ'))->toContain('nonce="nonce-XYZ"');
    });

    it('does not include nonce when none is given', function () {
        expect(makeCaptcha()->renderFooterJS())->not->toContain('nonce=');
    });

    it('contains the load event listener', function () {
        expect(makeCaptcha()->renderFooterJS())->toContain("addEventListener('load'");
    });

    it('contains grecaptcha.execute call', function () {
        expect(makeCaptcha()->renderFooterJS())->toContain('grecaptcha.execute()');
    });

    it('returns null when disabled', function () {
        expect(makeCaptcha(['enabled' => false])->renderFooterJS())->toBeNull();
    });
});

// ─── renderDebug (uses pipe operator internally) ──────────────────────────────

describe('renderDebug', function () {
    it('outputs console.log for every DEBUG_ELEMENTS entry', function () {
        $html = makeCaptcha()->renderDebug();

        foreach (InvisibleReCaptcha::DEBUG_ELEMENTS as $element) {
            expect($html)->toContain($element);
        }
    });

    it('returns null when disabled', function () {
        expect(makeCaptcha(['enabled' => false])->renderDebug())->toBeNull();
    });
});

// ─── render (uses pipe operator |> internally) ────────────────────────────────

describe('render', function () {
    it('combines polyfill + captchaHTML + footerJS via pipe operator', function () {
        $html = makeCaptcha()->render();

        expect($html)
            ->toContain(InvisibleReCaptcha::POLYFILL_URI)
            ->toContain('g-recaptcha')
            ->toContain(InvisibleReCaptcha::API_URI);
    });

    it('passes lang to the api url', function () {
        expect(makeCaptcha()->render('ro'))->toContain('?hl=ro');
    });

    it('passes nonce to the script tag', function () {
        expect(makeCaptcha()->render('en', 'my-nonce'))->toContain('nonce="my-nonce"');
    });

    it('returns null when disabled', function () {
        expect(makeCaptcha(['enabled' => false])->render())->toBeNull();
    });
});

// ─── renderCaptcha ────────────────────────────────────────────────────────────

describe('renderCaptcha', function () {
    it('returns the same output as render()', function () {
        $captcha = makeCaptcha();

        expect($captcha->renderCaptcha())->toBe($captcha->render());
    });

    it('returns null when disabled', function () {
        expect(makeCaptcha(['enabled' => false])->renderCaptcha())->toBeNull();
    });
});

// ─── verifyResponse (uses pipe operator |> internally) ────────────────────────

describe('verifyResponse', function () {
    it('returns true immediately when captcha is disabled', function () {
        expect(makeCaptcha(['enabled' => false])->verifyResponse('anything', '127.0.0.1'))->toBeTrue();
    });

    it('returns false for an empty response token', function () {
        expect(makeCaptcha()->verifyResponse('', '127.0.0.1'))->toBeFalse();
    });

    it('returns true when Google responds with success=true', function () {
        $captcha = makeCaptcha();
        $captcha->setClient(makeGuzzleMock(['success' => true]));

        expect($captcha->verifyResponse('valid-token', '127.0.0.1'))->toBeTrue();
    });

    it('returns false when Google responds with success=false', function () {
        $captcha = makeCaptcha();
        $captcha->setClient(makeGuzzleMock(['success' => false]));

        expect($captcha->verifyResponse('bad-token', '127.0.0.1'))->toBeFalse();
    });
});

// ─── verifyRequest ────────────────────────────────────────────────────────────

describe('verifyRequest', function () {
    it('returns true when Google confirms a valid Symfony Request', function () {
        $captcha = makeCaptcha();
        $captcha->setClient(makeGuzzleMock(['success' => true]));

        $request = Request::create('/', 'POST', ['g-recaptcha-response' => 'valid-token']);

        expect($captcha->verifyRequest($request))->toBeTrue();
    });

    it('returns false when the recaptcha response field is missing', function () {
        $captcha = makeCaptcha();
        $request = Request::create('/', 'POST');

        expect($captcha->verifyRequest($request))->toBeFalse();
    });
});
