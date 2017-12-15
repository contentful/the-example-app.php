<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2017 Contentful GmbH
 * @license   MIT
 */
declare(strict_types=1);

namespace App\Service;

use Contentful\Delivery\Client;
use Contentful\Delivery\DynamicEntry;
use Contentful\Delivery\Query;
use Contentful\Delivery\Space;
use Contentful\Exception\ApiException;

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
     * @var EntryStateChecker
     */
    private $entryStateChecker;

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @param State             $state
     * @param ClientFactory     $clientFactory
     * @param EntryStateChecker $entryStateChecker
     */
    public function __construct(State $state, ClientFactory $clientFactory, EntryStateChecker $entryStateChecker)
    {
        $this->state = $state;
        $this->entryStateChecker = $entryStateChecker;
        $this->clientFactory = $clientFactory;
        $this->client = $clientFactory->createClient($this->state->isDeliveryApi() ? self::API_DELIVERY : self::API_PREVIEW);
    }

    /**
     * Validates the given credentials by trying to make an API call.
     *
     * @param string $spaceId
     * @param string $accessToken
     * @param bool   $deliveryApi
     *
     * @throws ApiException if the credentials are not valid and an error response is returned from Contentful
     */
    public function validateCredentials(string $spaceId, string $accessToken, bool $deliveryApi = true): void
    {
        // We make an "empty" API call,
        // the result of which will depend on the validity of the credentials.
        // If any error should arise, the call will throw an exception.
        $this->clientFactory->createClient(
            $deliveryApi ? self::API_DELIVERY : self::API_PREVIEW,
            $spaceId,
            $accessToken
        )->getSpace();
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
            ->setInclude(2);

        if ($category) {
            $query->where('fields.categories.sys.id', $category->getId());
        }

        $courses = $this->client->getEntries($query)->getItems();

        if ($this->state->hasEditorialFeaturesLink()) {
            $this->entryStateChecker->computeState($courses, 1);
        }

        return $courses;
    }

    /**
     * Finds a course using its slug.
     * We use the collection endpoint, so we can prefetch linked entries
     * by using the include parameter.
     * Depending on the page, we can choose whether we can go as deep as
     * the lesson modules when working out the entry state.
     *
     * @param string $slug
     * @param bool   $includeLessonModules
     *
     * @return DynamicEntry|null
     */
    public function findCourse(string $slug, bool $includeLessonModules): ?DynamicEntry
    {
        $query = (new Query())
            ->where('fields.slug', $slug)
            ->setContentType('course')
            ->setLocale($this->state->getLocale())
            ->setInclude(3)
            ->setLimit(1);

        $course = $this->client->getEntries($query)->getItems()[0] ?? null;

        if ($course && $this->state->hasEditorialFeaturesLink()) {
            $this->entryStateChecker->computeState([$course], $includeLessonModules ? 3 : 2);
        }

        return $course;
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
            ->setInclude(3)
            ->setLimit(1);

        $landingPage = $this->client->getEntries($query)->getItems()[0] ?? null;

        if ($landingPage && $this->state->hasEditorialFeaturesLink()) {
            $this->entryStateChecker->computeState([$landingPage], 2);
        }

        return $landingPage;
    }
}
