<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2015-2018 Contentful GmbH
 * @license   MIT
 */

declare(strict_types=1);

namespace App\Cache;

use App\Service\ClientFactory;
use App\Service\Contentful;
use Contentful\Delivery\Cache\CacheWarmer;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class AppCacheWarmer implements CacheWarmerInterface
{
    /**
     * @var CacheWarmer
     */
    private $cacheWarmer;

    /**
     * @param ClientFactory          $clientFactory
     * @param CacheItemPoolInterface $cacheItemPool
     */
    public function __construct(ClientFactory $clientFactory, CacheItemPoolInterface $cacheItemPool)
    {
        $client = $clientFactory->createClient(Contentful::API_DELIVERY, null, null, false);
        $this->cacheWarmer = new CacheWarmer($client, $client->getResourcePool(), $cacheItemPool);
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        $this->cacheWarmer->warmUp();
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return true;
    }
}
