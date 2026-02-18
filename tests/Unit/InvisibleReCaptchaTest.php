<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Oriceon\InvisibleReCaptcha\InvisibleReCaptcha;
use Symfony\Component\HttpFoundation\Request;

// ─── Constructor & Getters ────────────────────────────────────────────────────

describe('constructor', function () {
    it('stores site key and secret key', function () {
        $captcha = makeCaptcha();

        expect($captcha->getSiteKey())->toBe(SITE_KEY)
            ->and($captcha->getSecretKey())->toBe(SECRET_KEY);
    });

    it('initialises a guzzle client', function () {
        expect(makeCaptcha()->getClient())->toBeInstanceOf(Client::class);
    });

    it('stores options', function () {
        expect(makeCaptcha()->getOptions())->toBe(OPTIONS);
    });
});

// ─── Options ──────────────────────────────────────────────────────────────────

describe('options', function () {
    it('returns a single option value', function () {
        expect(makeCaptcha()->getOption('timeout'))->toBe(5)
            ->and(makeCaptcha()->getOption('debug'))->toBeFalse();
    });

    it('returns default when option is missing', function () {
        expect(makeCaptcha()->getOption('nonExistent', 'default'))->toBe('default');
    });

    it('can set a single option', function () {
        $captcha = makeCaptcha();
        $captcha->setOption('debug', true);
        $captcha->setOption('timeout', 10);

        expect($captcha->getOption('debug'))->toBeTrue()
            ->and($captcha->getOption('timeout'))->toBe(10);
    });

    it('can replace all options at once', function () {
        $captcha  = makeCaptcha();
        $newOpts  = ['enabled' => false, 'hideBadge' => true, 'dataBadge' => 'inline', 'timeout' => 3, 'debug' => false];
        $captcha->setOptions($newOpts);

        expect($captcha->getOptions())->toBe($newOpts);
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
    it('renders a script tag with the polyfill url', function () {
        $html = makeCaptcha()->renderPolyfill();

        expect($html)
            ->toContain('<script')
            ->toContain(InvisibleReCaptcha::POLYFILL_URI);
    });

    it('returns null when disabled', function () {
        expect(makeCaptcha(['enabled' => false])->renderPolyfill())->toBeNull();
    });
});

// ─── renderCaptchaHTML ────────────────────────────────────────────────────────

describe('renderCaptchaHTML', function () {
    it('contains the g-recaptcha div with sitekey', function () {
        $html = makeCaptcha()->renderCaptchaHTML();

        expect($html)
            ->toContain('g-recaptcha')
            ->toContain(SITE_KEY)
            ->toContain('data-size="invisible"')
            ->toContain('data-callback="_submitForm"')
            ->toContain('data-badge="bottomright"');
    });

    it('contains the anchor div', function () {
        expect(makeCaptcha()->renderCaptchaHTML())->toContain('id="_g-recaptcha"');
    });

    it('hides badge with CSS when hideBadge is true', function () {
        $html = makeCaptcha(['hideBadge' => true])->renderCaptchaHTML();

        expect($html)->toContain('display:none');
    });

    it('does not include hide-badge CSS when hideBadge is false', function () {
        $html = makeCaptcha(['hideBadge' => false])->renderCaptchaHTML();

        expect($html)->not->toContain('display:none');
    });

    it('uses the configured badge position', function () {
        $html = makeCaptcha(['dataBadge' => 'bottomleft'])->renderCaptchaHTML();

        expect($html)->toContain('data-badge="bottomleft"');
    });

    it('returns null when disabled', function () {
        expect(makeCaptcha(['enabled' => false])->renderCaptchaHTML())->toBeNull();
    });
});

// ─── renderFooterJS ───────────────────────────────────────────────────────────

describe('renderFooterJS', function () {
    it('includes the api script tag', function () {
        $html = makeCaptcha()->renderFooterJS();

        expect($html)
            ->toContain('<script')
            ->toContain(InvisibleReCaptcha::API_URI);
    });

    it('appends hl param to api url when lang provided', function () {
        expect(makeCaptcha()->renderFooterJS('ro'))->toContain('?hl=ro');
    });

    it('includes nonce attribute when nonce is given', function () {
        expect(makeCaptcha()->renderFooterJS('en', 'nonce-XYZ123'))->toContain('nonce="nonce-XYZ123"');
    });

    it('does not include nonce when none is given', function () {
        expect(makeCaptcha()->renderFooterJS())->not->toContain('nonce=');
    });

    it('contains load event listener', function () {
        expect(makeCaptcha()->renderFooterJS())->toContain("addEventListener('load'");
    });

    it('contains _submitForm definition', function () {
        expect(makeCaptcha()->renderFooterJS())->toContain('_submitForm');
    });

    it('contains grecaptcha.execute call', function () {
        expect(makeCaptcha()->renderFooterJS())->toContain('grecaptcha.execute()');
    });

    it('returns null when disabled', function () {
        expect(makeCaptcha(['enabled' => false])->renderFooterJS())->toBeNull();
    });
});

// ─── renderDebug ──────────────────────────────────────────────────────────────

describe('renderDebug', function () {
    it('outputs console.log statements for each debug element', function () {
        $html = makeCaptcha()->renderDebug();

        foreach (InvisibleReCaptcha::DEBUG_ELEMENTS as $element) {
            expect($html)->toContain($element);
        }
    });

    it('returns null when disabled', function () {
        expect(makeCaptcha(['enabled' => false])->renderDebug())->toBeNull();
    });
});

// ─── render ───────────────────────────────────────────────────────────────────

describe('render', function () {
    it('renders all three parts combined', function () {
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
    it('delegates to render and returns the same output', function () {
        $captcha = makeCaptcha();

        expect($captcha->renderCaptcha())->toBe($captcha->render());
    });

    it('returns null when disabled', function () {
        expect(makeCaptcha(['enabled' => false])->renderCaptcha())->toBeNull();
    });
});

// ─── verifyResponse ───────────────────────────────────────────────────────────

describe('verifyResponse', function () {
    it('returns true immediately when captcha is disabled', function () {
        expect(makeCaptcha(['enabled' => false])->verifyResponse('anything', '127.0.0.1'))->toBeTrue();
    });

    it('returns false for an empty response token', function () {
        expect(makeCaptcha()->verifyResponse('', '127.0.0.1'))->toBeFalse();
    });

    it('returns true when google responds with success=true', function () {
        $captcha = makeCaptcha();
        $captcha->setClient(makeGuzzleMock(['success' => true]));

        expect($captcha->verifyResponse('valid-token', '127.0.0.1'))->toBeTrue();
    });

    it('returns false when google responds with success=false', function () {
        $captcha = makeCaptcha();
        $captcha->setClient(makeGuzzleMock(['success' => false]));

        expect($captcha->verifyResponse('bad-token', '127.0.0.1'))->toBeFalse();
    });
});

// ─── verifyRequest ────────────────────────────────────────────────────────────

describe('verifyRequest', function () {
    it('returns true when google responds with success for a Symfony request', function () {
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

// ─── Helpers ──────────────────────────────────────────────────────────────────

function makeGuzzleMock(array $body): Client
{
    $mock    = new MockHandler([new Response(200, [], json_encode($body))]);
    $handler = HandlerStack::create($mock);

    return new Client(['handler' => $handler]);
}
