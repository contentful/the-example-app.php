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
     * @var string
     */
    private $cacheDir;

    /**
     * @param State  $state
     * @param string $cacheDir
     */
    public function __construct(State $state, string $cacheDir)
    {
        $this->state = $state;
        $this->cacheDir = $cacheDir.'/contentful';
    }

    /**
     * Creates a token for the given API.
     * If now $spaceId and $accessToken are given, the default ones will be used.
     *
     * @param string      $api
     * @param string|null $spaceId
     * @param string|null $accessToken
     *
     * @return Client
     */
    public function createClient(string $api, string $spaceId = null, string $accessToken = null): Client
    {
        if ($api !== Contentful::API_DELIVERY && $api !== Contentful::API_PREVIEW) {
            throw new \InvalidArgumentException(sprintf(
                'Trying to instantiate a client for unknown API: %s.',
                $api
            ));
        }

        $spaceId = $spaceId ?: $this->state->getSpaceId();
        $accessToken = $accessToken ?: (
            $api === Contentful::API_DELIVERY
                ? $this->state->getDeliveryToken()
                : $this->state->getPreviewToken()
        );

        $client = new Client(
            $accessToken,
            $spaceId,
            $api === Contentful::API_PREVIEW,
            $this->state->getLocale(),
            ['cacheDir' => $this->cacheDir]
        );
        $client->setApplication(Kernel::APP_NAME, Kernel::APP_VERSION);

        return $client;
    }
}
