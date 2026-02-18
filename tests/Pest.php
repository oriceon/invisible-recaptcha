<?php

use Oriceon\InvisibleReCaptcha\InvisibleReCaptcha;

const SITE_KEY   = 'test_site_key';
const SECRET_KEY = 'test_secret_key';
const OPTIONS    = [
    'hideBadge' => false,
    'dataBadge' => 'bottomright',
    'timeout'   => 5,
    'debug'     => false,
    'enabled'   => true,
];

function makeCaptcha(array $overrides = []): InvisibleReCaptcha
{
    return new InvisibleReCaptcha(SITE_KEY, SECRET_KEY, array_merge(OPTIONS, $overrides));
}
