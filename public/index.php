<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2015-2018 Contentful GmbH
 * @license   MIT
 */

declare(strict_types=1);

use App\Kernel;
use Symfony\Component\Debug\Debug;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;

require __DIR__.'/../vendor/autoload.php';

// The check is to ensure we don't use .env in production
if (!isset($_SERVER['APP_ENV'])) {
    if (!\class_exists(Dotenv::class)) {
        throw new \RuntimeException('APP_ENV environment variable is not defined. You need to define environment variables for configuration or add "symfony/dotenv" as a Composer dependency to load variables from a .env file.');
    }
    (new Dotenv())->load(__DIR__.'/../.env');
}

$env = $_SERVER['APP_ENV'] ?? 'dev';
$debug = $_SERVER['APP_DEBUG'] ?? ('prod' !== $env && 'staging' !== $env);

if ($debug) {
    \umask(0000);

    Debug::enable();
}

$kernel = new Kernel($env, $debug);
$request = Request::createFromGlobals();

// This is enabled to allow for correctly handling HTTPS on Heroku:
// https://devcenter.heroku.com/articles/getting-started-with-symfony#trusting-the-load-balancer
// The code snippet in the Heroku docs is for the Symfony 3 branch;
// the way it worked was by adding headers to a blacklist by calling
// Request::setTrustedHeaderName($name, $value) with $value set to null.
// In Symfony 4, Request::setTrustedProxies() takes a second parameter,
// which defines a whitelist, so instead of removing the headers we don't want to trust,
// we explicitly state a bit field of which headers can be trusted.
Request::setTrustedProxies(
    [$request->server->get('REMOTE_ADDR')],
    Request::HEADER_X_FORWARDED_ALL ^ Request::HEADER_X_FORWARDED_HOST
);

$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
