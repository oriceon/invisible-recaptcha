Invisible reCAPTCHA
==========
![php-badge](https://img.shields.io/badge/php-%3E%3D%205.6-8892BF.svg)
[![packagist-badge](https://img.shields.io/packagist/v/oriceon/invisible-recaptcha.svg)](https://packagist.org/packages/oriceon/invisible-recaptcha)
[![Total Downloads](https://poser.pugx.org/oriceon/invisible-recaptcha/downloads)](https://packagist.org/packages/oriceon/invisible-recaptcha)
[![travis-badge](https://api.travis-ci.org/oriceon/invisible-recaptcha.svg?branch=master)](https://travis-ci.org/oriceon/invisible-recaptcha)

![invisible_recaptcha_demo](http://i.imgur.com/1dZ9XKn.png)

## Why Invisible reCAPTCHA?

Invisible reCAPTCHA is an improved version of reCAPTCHA v2(no captcha).
In reCAPTCHA v2, users need to click the button: "I'm not a robot" to prove they are human. In invisible reCAPTCHA, there will be not embed a captcha box for users to click. It's totally invisible! Only the badge will show on the buttom of the page to hint users that your website is using this technology. (The badge could be hidden, but not suggested.)

## Installation

```
composer require oriceon/invisible-recaptcha
```

### Configuration
Before you set your config, remember to choose `invisible reCAPTCHA` while applying for keys.
![invisible_recaptcha_setting](http://i.imgur.com/zIAlKbY.jpg)

Add `INVISIBLE_RECAPTCHA_SITEKEY`, `INVISIBLE_RECAPTCHA_SECRETKEY` to **.env** file.

```
// required
INVISIBLE_RECAPTCHA_SITEKEY={siteKey}
INVISIBLE_RECAPTCHA_SECRETKEY={secretKey}

// optional
INVISIBLE_RECAPTCHA_BADGEHIDE=false
INVISIBLE_RECAPTCHA_DATABADGE='bottomright'
INVISIBLE_RECAPTCHA_TIMEOUT=5
INVISIBLE_RECAPTCHA_DEBUG=false
INVISIBLE_RECAPTCHA_ENABLED=true
```

> There are three different captcha styles you can set: `bottomright`, `bottomleft`, `inline`

> If you set `INVISIBLE_RECAPTCHA_BADGEHIDE` to true, you can hide the badge logo.

> You can see the binding status of those catcha elements on browser console by setting `INVISIBLE_RECAPTCHA_DEBUG` as true.

### Usage

Before you render the captcha, please keep those notices in mind:

* `render()` or `renderHTML()` function needs to be called within a form element.
* You have to ensure the `type` attribute of your submit button has to be `submit`.
* There can only be one submit button in your form.

##### Display reCAPTCHA in Your View

```php
{!! app('captcha')->render() !!}

// or you can use this in blade
@captcha
```

With custom language support:

```php
{!! app('captcha')->render('en') !!}

// or you can use this in blade
@captcha('en')
```

##### Usage with Javascript frameworks like VueJS:

The `render()` process includes three distinct sections that can be rendered separately incase you're using the package with a framework like VueJS which throws console errors when `<script>` tags are included in templates.

You can render the polyfill (do this somewhere like the head of your HTML:)

```php
{!! app('captcha')->renderPolyfill() !!}

// Or with blade directive:
@captchaPolyfill
```

You can render the HTML using this following, this needs to be INSIDE your `<form>` tag:

```php
{!! app('captcha')->renderCaptchaHTML() !!}

// Or with blade directive:
@captchaHTML
```

And you can render the neccessary `<script>` tags including the optional language support by using:

```php
// The argument is optional.
{!! app('captcha')->renderFooterJS('en') !!}

// Or with blade directive:
@captchaScripts

// blade directive, with language support:
@captchaScripts('en')
```

##### Validation

Add `'g-recaptcha-response' => 'required|captcha'` to rules array.

```php
$validate = Validator::make(Input::all(), [
    'g-recaptcha-response' => 'required|captcha'
]);

```

## Credits 

* anhskohbo (the author of no-captcha package)
* albertcht (the author of fworked no-captcha package)
* [Contributors](https://github.com/oriceon/invisible-recaptcha/graphs/contributors)