<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Oriceon\InvisibleReCaptcha\InvisibleReCaptcha;

// ─── Shared constants ─────────────────────────────────────────────────────────

const SITE_KEY   = 'test_site_key';
const SECRET_KEY = 'test_secret_key';
const RAW_OPTIONS = [
    'enabled'   => true,
    'hideBadge' => false,
    'dataBadge' => 'bottomright',
    'timeout'   => 5,
    'debug'     => false,
];

// ─── Shared helpers ───────────────────────────────────────────────────────────

function makeCaptcha(array $overrides = []): InvisibleReCaptcha
{
    return new InvisibleReCaptcha(SITE_KEY, SECRET_KEY, array_merge(RAW_OPTIONS, $overrides));
}

function makeGuzzleMock(array $body): Client
{
    $mock    = new MockHandler([new Response(200, [], json_encode($body))]);
    $handler = HandlerStack::create($mock);

    return new Client(['handler' => $handler]);
}
