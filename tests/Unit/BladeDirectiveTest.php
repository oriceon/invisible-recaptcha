<?php

use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Oriceon\InvisibleReCaptcha\InvisibleReCaptchaServiceProvider;

// ─── Helper ───────────────────────────────────────────────────────────────────

function makeBlade(): BladeCompiler
{
    $app = Container::getInstance();
    $app->instance('captcha', makeCaptcha());

    $blade    = new BladeCompiler(new Filesystem(), sys_get_temp_dir());
    $provider = new InvisibleReCaptchaServiceProvider($app);
    $provider->addBladeDirective($blade);

    return $blade;
}

// Singleton so all tests in this file share one compiled instance
function blade(): BladeCompiler
{
    static $instance = null;
    return $instance ??= makeBlade();
}

// ─── @captcha ─────────────────────────────────────────────────────────────────

describe('@captcha directive', function () {
    it('compiles @captcha() without arguments', function () {
        expect(blade()->compileString('@captcha()'))
            ->toBe("<?php echo app('captcha')->renderCaptcha(); ?>");
    });

    it('compiles @captcha with lang argument', function () {
        expect(blade()->compileString("@captcha('ro')"))
            ->toBe("<?php echo app('captcha')->renderCaptcha('ro'); ?>");
    });

    it('compiles @captcha with lang and nonce arguments', function () {
        expect(blade()->compileString("@captcha('ro', 'nonce-ABC')"))
            ->toBe("<?php echo app('captcha')->renderCaptcha('ro', 'nonce-ABC'); ?>");
    });
});

// ─── @captchaPolyfill ─────────────────────────────────────────────────────────

describe('@captchaPolyfill directive', function () {
    it('compiles @captchaPolyfill()', function () {
        expect(blade()->compileString('@captchaPolyfill()'))
            ->toBe("<?php echo app('captcha')->renderPolyfill(); ?>");
    });
});

// ─── @captchaHTML ─────────────────────────────────────────────────────────────

describe('@captchaHTML directive', function () {
    it('compiles @captchaHTML()', function () {
        expect(blade()->compileString('@captchaHTML()'))
            ->toBe("<?php echo app('captcha')->renderCaptchaHTML(); ?>");
    });
});

// ─── @captchaScripts ──────────────────────────────────────────────────────────

describe('@captchaScripts directive', function () {
    it('compiles @captchaScripts() without arguments', function () {
        expect(blade()->compileString('@captchaScripts()'))
            ->toBe("<?php echo app('captcha')->renderFooterJS(); ?>");
    });

    it('compiles @captchaScripts with lang argument', function () {
        expect(blade()->compileString("@captchaScripts('ro')"))
            ->toBe("<?php echo app('captcha')->renderFooterJS('ro'); ?>");
    });

    it('compiles @captchaScripts with lang and nonce arguments', function () {
        expect(blade()->compileString("@captchaScripts('ro', 'nonce-ABC')"))
            ->toBe("<?php echo app('captcha')->renderFooterJS('ro', 'nonce-ABC'); ?>");
    });
});
