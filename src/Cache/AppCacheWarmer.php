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
     * @param string $spaceId
     * @param string $deliveryToken
     */
    public function __construct(string $spaceId, string $deliveryToken)
    {
        $this->client = new Client($deliveryToken, $spaceId);
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
