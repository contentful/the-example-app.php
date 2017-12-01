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
use Contentful\Delivery\DynamicEntry;
use Contentful\Delivery\Query;
use Contentful\Delivery\Space;
use Contentful\Exception\ApiException;
use Contentful\Exception\NotFoundException;

/**
 * Contentful class.
 *
 * This class acts as a wrapper around the Content Delivery SDK.
 * It implements special logic for handling dual clients (for Preview and Delivery APIs).
 */
class Contentful
{
    /**
     * @var string
     */
    public const API_DELIVERY = 'cda';

    /**
     * @var string
     */
    public const API_PREVIEW = 'cpa';

    /**
     * @var string
     */
    public const COOKIE_SETTINGS_NAME = 'theExampleAppSettings';

    /**
     * @var Client
     */
    private $client;

    /**
     * @var State
     */
    private $state;

    /**
     * @param State  $state
     * @param string $cacheDir
     */
    public function __construct(State $state, string $cacheDir)
    {
        $this->state = $state;
        $options = ['cacheDir' => $cacheDir.'/contentful'];

        $this->client = $this->state->isDeliveryApi()
            ? new Client($this->state->getDeliveryToken(), $this->state->getSpaceId(), false, $this->state->getLocale(), $options)
            : new Client($this->state->getPreviewToken(), $this->state->getSpaceId(), true, $this->state->getLocale(), $options);

        $this->client->setApplication(Kernel::APP_NAME, Kernel::APP_VERSION);
    }

    /**
     * Validates the given credentials by trying to make an API call.
     *
     * @param string $spaceId
     * @param string $token
     * @param bool   $deliveryApi
     *
     * @throws ApiException if the credentials are not valid and an error response is returned from Contentful
     */
    public function validateCredentials(string $spaceId, string $token, bool $deliveryApi = true): void
    {
        // We make an "empty" API call,
        // the result of which will depend on the validity of the credentials.
        // If any error should arise, the call will throw an exception.
        $client = new Client($token, $spaceId, !$deliveryApi);
        $client->setApplication('the-example-app.php', Kernel::APP_VERSION);
        $client->getSpace();
    }

    /**
     * Attaches to an entry metadata about its state.
     * The entry will have two extra fields defined:
     * - draft: whether the entry has not been published yet
     * - pendingChanges: whether the entry has already been published,
     *     but some changes have been made in the meanwhile.
     *
     * @param DynamicEntry $entry
     *
     * @return DynamicEntry
     */
    private function attachEntryState(DynamicEntry $entry): DynamicEntry
    {
        $entry->draft = false;
        $entry->pendingChanges = false;

        if ($this->state->hasEditorialFeaturesEnabled() && $this->state->getApi() == self::API_PREVIEW) {
            $deliveryClient = new Client($this->state->getDeliveryToken(), $this->state->getSpaceId(), false, $this->state->getLocale());

            try {
                $publishedEntry = $deliveryClient->getEntry($entry->getId());

                if ($entry->getUpdatedAt() != $publishedEntry->getUpdatedAt()) {
                    $entry->pendingChanges = true;
                }
            } catch (NotFoundException $exception) {
                $entry->draft = true;
            }
        }

        return $entry;
    }

    /**
     * Finds the space object currently in use.
     *
     * @return Space
     */
    public function findSpace(): Space
    {
        return $this->client->getSpace();
    }

    /**
     * Finds the available courses, sorted alphabetically.
     *
     * @return DynamicEntry[]
     */
    public function findCategories(): array
    {
        $query = (new Query())
            ->setContentType('category')
            ->orderBy('fields.title')
            ->setLocale($this->state->getLocale());

        return $this->client->getEntries($query)->getItems();
    }

    /**
     * Finds the available courses, sorted by creation date.
     *
     * @param DynamicEntry|null $category
     *
     * @return DynamicEntry[]
     */
    public function findCourses(?DynamicEntry $category): array
    {
        $query = (new Query())
            ->setContentType('course')
            ->setLocale($this->state->getLocale())
            ->orderBy('-sys.createdAt')
            ->setInclude(6);

        if ($category) {
            $query->where('fields.categories.sys.id', $category->getId());
        }

        $courses = $this->client->getEntries($query);

        return array_map([$this, 'attachEntryState'], $courses->getItems());
    }

    /**
     * Finds a course using its slug.
     * We use the collection endpoint, so we can prefetch linked entries
     * by using the include parameter.
     *
     * @param string $slug
     *
     * @return DynamicEntry|null
     */
    public function findCourse(string $slug): ?DynamicEntry
    {
        $query = (new Query())
            ->where('fields.slug', $slug)
            ->setContentType('course')
            ->setLocale($this->state->getLocale())
            ->setInclude(6);

        $courses = $this->client->getEntries($query);

        if (!count($courses)) {
            return null;
        }

        return $this->attachEntryState($courses[0]);
    }

    /**
     * Finds a landing page using its slug.
     * We use the collection endpoint, so we can prefetch linked entries
     * by using the include parameter.
     *
     * @param string $slug
     *
     * @return DynamicEntry|null
     */
    public function findLandingPage(string $slug): ?DynamicEntry
    {
        $query = (new Query())
            ->where('fields.slug', $slug)
            ->setContentType('layout')
            ->setLocale($this->state->getLocale())
            ->setInclude(6);

        $landingPages = $this->client->getEntries($query);

        if (!count($landingPages)) {
            return null;
        }

        return $this->attachEntryState($landingPages[0]);
    }
}
