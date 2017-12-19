<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2017 Contentful GmbH
 * @license   MIT
 */
declare(strict_types=1);

namespace App\Cache;

use Contentful\Delivery\Cache\CacheWarmer;
use Contentful\Delivery\Client;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class AppCacheWarmer implements CacheWarmerInterface
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @param array $credentials
     */
    public function __construct(array $credentials)
    {
        $this->client = new Client($credentials['delivery_token'], $credentials['space_id']);
    }

    public function warmUp($cacheDir)
    {
        $warmer = new CacheWarmer($this->client);

        $warmer->warmUp($cacheDir.'/contentful');
    }

    public function isOptional()
    {
        return true;
    }
}
