<?php

namespace Oriceon\InvisibleReCaptcha;

use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;

class InvisibleReCaptchaServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->bootConfig();

        $this->app['validator']->extend('captcha', function (string $attribute, string $value): bool {
            return $this->app['captcha']->verifyResponse($value, $this->app['request']->getClientIp());
        });
    }

    public function register(): void
    {
        $this->app->singleton('captcha', function ($app): InvisibleReCaptcha {
            return new InvisibleReCaptcha(
                siteKey:    $app['config']['captcha.siteKey'],
                secretKey:  $app['config']['captcha.secretKey'],
                rawOptions: $app['config']['captcha.options'],
            );
        });

        $this->app->afterResolving('blade.compiler', function (): void {
            $this->addBladeDirective($this->app['blade.compiler']);
        });
    }

    protected function bootConfig(): void
    {
        $path = __DIR__ . '/config/captcha.php';

        $this->mergeConfigFrom($path, 'captcha');

        if (function_exists('config_path')) {
            $this->publishes([$path => config_path('captcha.php')]);
        }
    }

    public function provides(): array
    {
        return ['captcha'];
    }

    public function addBladeDirective(BladeCompiler $blade): void
    {
        $blade->directive('captcha', fn($args) => "<?php echo app('captcha')->renderCaptcha({$args}); ?>");

        $blade->directive('captchaPolyfill', fn() => "<?php echo app('captcha')->renderPolyfill(); ?>");

        $blade->directive('captchaHTML', fn() => "<?php echo app('captcha')->renderCaptchaHTML(); ?>");

        $blade->directive('captchaScripts', fn($args) => "<?php echo app('captcha')->renderFooterJS({$args}); ?>");
    }
}
