<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2017 Contentful GmbH
 * @license   MIT
 */
declare(strict_types=1);

namespace App\Service;

use App\Kernel;
use Contentful\Delivery\Client;
use Psr\Cache\CacheItemPoolInterface;

/**
 * ClientFactory class.
 *
 * This class is responsible for instantiating Contentful Client objects.
 */
class ClientFactory
{
    /**
     * @var State
     */
    private $state;

    /**
     * @var CacheItemPoolInterface
     */
    private $cacheItemPool;

    /**
     * @var string
     */
    private $deliveryApiUrl;

    /**
     * @var string
     */
    private $previewApiUrl;

    /**
     * @param State                  $state
     * @param CacheItemPoolInterface $cacheItemPool
     * @param string                 $deliveryApiUrl
     * @param string                 $previewApiUrl
     */
    public function __construct(State $state, CacheItemPoolInterface $cacheItemPool, string $deliveryApiUrl, string $previewApiUrl)
    {
        $this->state = $state;
        $this->cacheItemPool = $cacheItemPool;
        $this->deliveryApiUrl = $deliveryApiUrl;
        $this->previewApiUrl = $previewApiUrl;
    }

    /**
     * Creates a token for the given API.
     * If now $spaceId and $accessToken are given, the default ones will be used.
     *
     * @param string      $api
     * @param string|null $spaceId
     * @param string|null $accessToken
     * @param bool        $useCache
     *
     * @return Client
     */
    public function createClient(string $api, string $spaceId = null, string $accessToken = null, bool $useCache = true): Client
    {
        if (Contentful::API_DELIVERY !== $api && Contentful::API_PREVIEW !== $api) {
            throw new \InvalidArgumentException(sprintf(
                'Trying to instantiate a client for unknown API: %s.',
                $api
            ));
        }

        $spaceId = $spaceId ?: $this->state->getSpaceId();
        $accessToken = $accessToken ?: (
            Contentful::API_DELIVERY === $api
                ? $this->state->getDeliveryToken()
                : $this->state->getPreviewToken()
        );

        // WARNING:
        // This is not needed during normal use.
        // URLs are determined automatically from the Client,
        // but here we need to make them configurable for internal purposes.
        // Overall, you might need the `uriOverride` option (for instance)
        // for setting up a custom proxy, but for most cases this is not something
        // you should care about too much.
        // This means that in normal use, we wouldn't have environment variables
        // for defining these URLs, and they wouldn't be injected in this factory.
        $uri = Contentful::API_DELIVERY === $api
            ? $this->deliveryApiUrl
            : $this->previewApiUrl;

        $options = ['baseUri' => $uri];
        if ($useCache) {
            $options['cache'] = $this->cacheItemPool;
        }

        $client = new Client(
            $accessToken,
            $spaceId,
            'master',
            Contentful::API_PREVIEW === $api,
            $this->state->getLocale(),
            $options
        );
        $client->setApplication(Kernel::APP_NAME, Kernel::APP_VERSION);

        return $client;
    }
}
