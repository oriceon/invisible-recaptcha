<?php

use GuzzleHttp\Client;
use Oriceon\InvisibleReCaptcha\Data\CaptchaOptions;
use Oriceon\InvisibleReCaptcha\InvisibleReCaptcha;
use Symfony\Component\HttpFoundation\Request;

// ─── Constructor ──────────────────────────────────────────────────────────────

describe('constructor', function () {
    it('stores siteKey and secretKey as readonly properties', function () {
        $captcha = makeCaptcha();

        expect($captcha->getSiteKey())->toBe(SITE_KEY)
            ->and($captcha->getSecretKey())->toBe(SECRET_KEY);
    });

    it('initialises a Guzzle client', function () {
        expect(makeCaptcha()->getClient())->toBeInstanceOf(Client::class);
    });

    it('builds a CaptchaOptions value-object from the raw array', function () {
        expect(makeCaptcha()->getOptions())->toBeInstanceOf(CaptchaOptions::class);
    });

    it('maps raw options to CaptchaOptions properties', function () {
        $opts = makeCaptcha()->getOptions();

        expect($opts->enabled)->toBeTrue()
            ->and($opts->hideBadge)->toBeFalse()
            ->and($opts->timeout)->toBe(5)
            ->and($opts->debug)->toBeFalse()
            ->and($opts->scoreThreshold)->toBe(0.5)
            ->and($opts->action)->toBe('submit');
    });
});

// ─── setOption / getOption ────────────────────────────────────────────────────

describe('setOption / getOption', function () {
    it('reads each known option key', function () {
        $captcha = makeCaptcha();

        expect($captcha->getOption('enabled'))->toBeTrue()
            ->and($captcha->getOption('hideBadge'))->toBeFalse()
            ->and($captcha->getOption('debug'))->toBeFalse()
            ->and($captcha->getOption('timeout'))->toBe(5)
            ->and($captcha->getOption('scoreThreshold'))->toBe(0.5)
            ->and($captcha->getOption('action'))->toBe('submit');
    });

    it('returns default for unknown key', function () {
        expect(makeCaptcha()->getOption('nonExistent', 'fallback'))->toBe('fallback');
    });

    it('updates enabled via setOption (clone-with internally)', function () {
        $captcha = makeCaptcha();
        $captcha->setOption('enabled', false);

        expect($captcha->getOption('enabled'))->toBeFalse();
    });

    it('updates scoreThreshold via setOption', function () {
        $captcha = makeCaptcha();
        $captcha->setOption('scoreThreshold', 0.8);

        expect($captcha->getOption('scoreThreshold'))->toBe(0.8);
    });

    it('updates action via setOption', function () {
        $captcha = makeCaptcha();
        $captcha->setOption('action', 'login');

        expect($captcha->getOption('action'))->toBe('login');
    });

    it('updates timeout via setOption', function () {
        $captcha = makeCaptcha();
        $captcha->setOption('timeout', 30);

        expect($captcha->getOption('timeout'))->toBe(30);
    });
});

describe('setOptions', function () {
    it('replaces all options at once', function () {
        $captcha = makeCaptcha();
        $captcha->setOptions(['enabled' => false, 'scoreThreshold' => 0.9, 'action' => 'login', 'timeout' => 3, 'debug' => true, 'hideBadge' => true]);

        $opts = $captcha->getOptions();

        expect($opts->enabled)->toBeFalse()
            ->and($opts->scoreThreshold)->toBe(0.9)
            ->and($opts->action)->toBe('login')
            ->and($opts->timeout)->toBe(3)
            ->and($opts->debug)->toBeTrue()
            ->and($opts->hideBadge)->toBeTrue();
    });
});

// ─── getCaptchaJs — v3 includes ?render=SITE_KEY ──────────────────────────────

describe('getCaptchaJs', function () {
    it('returns api url with ?render=SITE_KEY', function () {
        expect(makeCaptcha()->getCaptchaJs())
            ->toBe(InvisibleReCaptcha::API_URI . '?render=' . SITE_KEY);
    });

    it('appends &hl=lang when lang is given', function () {
        expect(makeCaptcha()->getCaptchaJs('ro'))
            ->toBe(InvisibleReCaptcha::API_URI . '?render=' . SITE_KEY . '&hl=ro');
    });

    it('returns null when disabled', function () {
        expect(makeCaptcha(['enabled' => false])->getCaptchaJs())->toBeNull();
    });
});

// ─── getPolyfillJs ────────────────────────────────────────────────────────────

describe('getPolyfillJs', function () {
    it('returns the polyfill cdn url', function () {
        expect(makeCaptcha()->getPolyfillJs())->toBe(InvisibleReCaptcha::POLYFILL_URI);
    });

    it('returns null when disabled', function () {
        expect(makeCaptcha(['enabled' => false])->getPolyfillJs())->toBeNull();
    });
});

// ─── renderPolyfill ───────────────────────────────────────────────────────────

describe('renderPolyfill', function () {
    it('renders a <script> tag with the polyfill url', function () {
        expect(makeCaptcha()->renderPolyfill())
            ->toContain('<script')
            ->toContain(InvisibleReCaptcha::POLYFILL_URI);
    });

    it('returns null when disabled', function () {
        expect(makeCaptcha(['enabled' => false])->renderPolyfill())->toBeNull();
    });
});

// ─── renderCaptchaHTML — v3: hidden input, no widget div ─────────────────────

describe('renderCaptchaHTML', function () {
    it('renders a hidden input named g-recaptcha-response', function () {
        $html = makeCaptcha()->renderCaptchaHTML();

        expect($html)
            ->toContain('type="hidden"')
            ->toContain('name="g-recaptcha-response"')
            ->toContain('id="g-recaptcha-response"');
    });

    it('does NOT render a g-recaptcha widget div (v3 has no widget)', function () {
        expect(makeCaptcha()->renderCaptchaHTML())
            ->not->toContain('data-size')
            ->not->toContain('data-callback')
            ->not->toContain('data-badge');
    });

    it('adds visibility:hidden CSS for badge when hideBadge is true', function () {
        expect(makeCaptcha(['hideBadge' => true])->renderCaptchaHTML())
            ->toContain('.grecaptcha-badge')
            ->toContain('visibility: hidden');
    });

    it('does not inject badge CSS when hideBadge is false', function () {
        expect(makeCaptcha(['hideBadge' => false])->renderCaptchaHTML())
            ->not->toContain('visibility: hidden');
    });

    it('returns null when disabled', function () {
        expect(makeCaptcha(['enabled' => false])->renderCaptchaHTML())->toBeNull();
    });
});

// ─── renderFooterJS — v3: grecaptcha.ready + execute ─────────────────────────

describe('renderFooterJS', function () {
    it('includes the v3 api script with ?render=SITE_KEY', function () {
        $html = makeCaptcha()->renderFooterJS();

        expect($html)
            ->toContain('<script')
            ->toContain('?render=' . SITE_KEY);
    });

    it('appends &hl=lang when lang is provided', function () {
        expect(makeCaptcha()->renderFooterJS('ro'))->toContain('&hl=ro');
    });

    it('includes nonce on both script tags', function () {
        $html = makeCaptcha()->renderFooterJS('en', 'nonce-ABC');

        expect(substr_count($html, 'nonce="nonce-ABC"'))->toBe(2);
    });

    it('does not include nonce when none is given', function () {
        expect(makeCaptcha()->renderFooterJS())->not->toContain('nonce=');
    });

    it('uses grecaptcha.ready()', function () {
        expect(makeCaptcha()->renderFooterJS())->toContain('grecaptcha.ready(');
    });

    it('calls grecaptcha.execute with site key and action', function () {
        $html = makeCaptcha()->renderFooterJS();

        expect($html)
            ->toContain("grecaptcha.execute('" . SITE_KEY . "'")
            ->toContain("{action:'submit'}");
    });

    it('uses a configured action in execute call', function () {
        expect(makeCaptcha(['action' => 'login'])->renderFooterJS())
            ->toContain("{action:'login'}");
    });

    it('injects token into g-recaptcha-response hidden input', function () {
        expect(makeCaptcha()->renderFooterJS())
            ->toContain("getElementById('g-recaptcha-response').value=token");
    });

    it('calls form.submit() after token injection', function () {
        expect(makeCaptcha()->renderFooterJS())->toContain('_form.submit()');
    });

    it('listens to form submit event and prevents default', function () {
        $html = makeCaptcha()->renderFooterJS();

        expect($html)
            ->toContain("addEventListener('submit'")
            ->toContain('e.preventDefault()');
    });

    it('returns null when disabled', function () {
        expect(makeCaptcha(['enabled' => false])->renderFooterJS())->toBeNull();
    });
});

// ─── renderDebug ──────────────────────────────────────────────────────────────

describe('renderDebug', function () {
    it('outputs console.log for each v3 debug element', function () {
        $html = makeCaptcha()->renderDebug();

        foreach (InvisibleReCaptcha::DEBUG_ELEMENTS as $element) {
            expect($html)->toContain($element);
        }
    });

    it('returns null when disabled', function () {
        expect(makeCaptcha(['enabled' => false])->renderDebug())->toBeNull();
    });
});

// ─── render (pipe operator |>) ────────────────────────────────────────────────

describe('render', function () {
    it('combines polyfill + hidden input + footer JS via pipe operator', function () {
        $html = makeCaptcha()->render();

        expect($html)
            ->toContain(InvisibleReCaptcha::POLYFILL_URI)
            ->toContain('g-recaptcha-response')
            ->toContain('?render=' . SITE_KEY);
    });

    it('passes lang to the api url', function () {
        expect(makeCaptcha()->render('ro'))->toContain('&hl=ro');
    });

    it('passes nonce to script tags', function () {
        expect(makeCaptcha()->render('en', 'nonce-XYZ'))->toContain('nonce="nonce-XYZ"');
    });

    it('returns null when disabled', function () {
        expect(makeCaptcha(['enabled' => false])->render())->toBeNull();
    });
});

// ─── renderCaptcha ────────────────────────────────────────────────────────────

describe('renderCaptcha', function () {
    it('returns same output as render()', function () {
        $captcha = makeCaptcha();
        expect($captcha->renderCaptcha())->toBe($captcha->render());
    });

    it('returns null when disabled', function () {
        expect(makeCaptcha(['enabled' => false])->renderCaptcha())->toBeNull();
    });
});

// ─── verifyResponse — v3: checks success + score + action ────────────────────

describe('verifyResponse', function () {
    it('returns true immediately when captcha is disabled', function () {
        expect(makeCaptcha(['enabled' => false])->verifyResponse('anything', '127.0.0.1'))->toBeTrue();
    });

    it('returns false for an empty token', function () {
        expect(makeCaptcha()->verifyResponse('', '127.0.0.1'))->toBeFalse();
    });

    it('returns true when success=true, score>=threshold, and action matches', function () {
        $captcha = makeCaptcha();
        $captcha->setClient(makeGuzzleMock(['success' => true, 'score' => 0.9, 'action' => 'submit']));

        expect($captcha->verifyResponse('valid-token', '127.0.0.1'))->toBeTrue();
    });

    it('returns false when score is below the threshold', function () {
        $captcha = makeCaptcha();
        $captcha->setClient(makeGuzzleMock(['success' => true, 'score' => 0.2, 'action' => 'submit']));

        expect($captcha->verifyResponse('low-score-token', '127.0.0.1'))->toBeFalse();
    });

    it('returns false when action does not match configured action', function () {
        $captcha = makeCaptcha();
        $captcha->setClient(makeGuzzleMock(['success' => true, 'score' => 0.9, 'action' => 'login']));

        expect($captcha->verifyResponse('wrong-action-token', '127.0.0.1'))->toBeFalse();
    });

    it('returns false when Google returns success=false', function () {
        $captcha = makeCaptcha();
        $captcha->setClient(makeGuzzleMock(['success' => false, 'score' => 0.9, 'action' => 'submit']));

        expect($captcha->verifyResponse('bad-token', '127.0.0.1'))->toBeFalse();
    });

    it('accepts score exactly at the threshold boundary', function () {
        $captcha = makeCaptcha(['scoreThreshold' => 0.5]);
        $captcha->setClient(makeGuzzleMock(['success' => true, 'score' => 0.5, 'action' => 'submit']));

        expect($captcha->verifyResponse('boundary-token', '127.0.0.1'))->toBeTrue();
    });

    it('rejects score just below the threshold boundary', function () {
        $captcha = makeCaptcha(['scoreThreshold' => 0.5]);
        $captcha->setClient(makeGuzzleMock(['success' => true, 'score' => 0.49, 'action' => 'submit']));

        expect($captcha->verifyResponse('below-boundary-token', '127.0.0.1'))->toBeFalse();
    });
});

// ─── verifyRequest ────────────────────────────────────────────────────────────

describe('verifyRequest', function () {
    it('returns true when Google confirms a valid Symfony Request', function () {
        $captcha = makeCaptcha();
        $captcha->setClient(makeGuzzleMock(['success' => true, 'score' => 0.9, 'action' => 'submit']));

        $request = Request::create('/', 'POST', ['g-recaptcha-response' => 'valid-token']);

        expect($captcha->verifyRequest($request))->toBeTrue();
    });

    it('returns false when g-recaptcha-response field is missing', function () {
        $captcha = makeCaptcha();
        $request = Request::create('/', 'POST');

        expect($captcha->verifyRequest($request))->toBeFalse();
    });
});
