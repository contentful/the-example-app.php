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
     * @var string
     */
    private $cacheDir;

    /**
     * A map of methods for accessing linked entries
     * based on the entry content type.
     *
     * @var string[]
     */
    private $linkedEntriesMethods = [
        'layout' => 'getContentModules',
        'course' => 'getLessons',
        'lesson' => 'getModules',
    ];

    /**
     * @param State  $state
     * @param string $cacheDir
     */
    public function __construct(State $state, string $cacheDir)
    {
        $this->state = $state;
        $this->cacheDir = $cacheDir.'/contentful';

        $this->client = $this->createClient($this->state->isDeliveryApi());
        $this->client->setApplication(Kernel::APP_NAME, Kernel::APP_VERSION);
    }

    /**
     * @param bool $deliveryApi
     *
     * @return Client
     */
    private function createClient(bool $deliveryApi): Client
    {
        return new Client(
            $deliveryApi ? $this->state->getDeliveryToken() : $this->state->getPreviewToken(),
            $this->state->getSpaceId(),
            !$deliveryApi,
            $this->state->getLocale(),
            ['cacheDir' => $this->cacheDir]
        );
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
            $this->computeState($courses, 1);
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
            $this->computeState([$course], $includeLessonModules ? 3 : 2);
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
            $this->computeState([$landingPage], 2);
        }

        return $landingPage;
    }

    /**
     * @param DynamicEntry[] $entries
     * @param int            $depth
     *
     * @return void
     */
    private function computeState(array $entries, int $depth): void
    {
        $deliveryEntries = $this->fetchDeliveryEntries($entries);

        foreach ($entries as $entry) {
            $this->attachEntryState($entry, $deliveryEntries, $depth);
        }
    }

    /**
     * Extracts the meaningful IDs fro the given preview entries (including nested ones),
     * and then queries the Delivery API in order to get a list with the corresponding,
     * published entries.
     *
     * @param DynamicEntry[] $entries
     *
     * @return DynamicEntry[]
     */
    private function fetchDeliveryEntries(array $entries): array
    {
        $ids = $this->extractIdsForComparison($entries);
        $query = (new Query())
            ->setInclude(0)
            ->where('sys.id', $ids, 'in');

        $entries = [];
        foreach ($this->createClient(true)->getEntries($query) as $entry) {
            $entries[$entry->getId()] = $entry;
        }

        return $entries;
    }

    /**
     * Given an array of entries, it will extract all IDs including from the nested ones.
     *
     * @param DynamicEntry[] $entries
     *
     * @return string[]
     */
    private function extractIdsForComparison(array $entries): array
    {
        $ids = [];

        foreach ($entries as $entry) {
            $ids[] = $entry->getId();
            $method = $this->linkedEntriesMethods[$entry->getContentType()->getId()] ?? null;

            if (!$method) {
                continue;
            }

            foreach ($entry->$method() as $linkedEntry) {
                $ids += \array_merge($ids, $this->extractIdsForComparison([$linkedEntry]));
            }
        }

        return \array_unique($ids);
    }

    /**
     * Attaches to an entry metadata about its state.
     * This is done by comparing it to another entry, which was loaded
     * from the Delivery API.
     * The entry will have two extra fields defined:
     * - draft: whether the entry has not been published yet
     * - pendingChanges: whether the entry has already been published,
     *     but some changes have been made in the meanwhile.
     *
     * @param DynamicEntry   $previewEntry
     * @param DynamicEntry[] $deliveryEntries An array where the entry ID is used as key
     * @param int            $depth
     *
     * @return void
     */
    private function attachEntryState(DynamicEntry $previewEntry, array $deliveryEntries, int $depth = 1): void
    {
        // Bail early if we've reached the limit of configured nesting.
        if ($depth == 0) {
            return;
        }

        $previewEntry->draft = false;
        $previewEntry->pendingChanges = false;

        $deliveryEntry = $deliveryEntries[$previewEntry->getId()] ?? null;

        // If no entry is found, it means it's hasn't been published yet.
        if (!$deliveryEntry) {
            $previewEntry->draft = true;
        }

        // Different updatedAt values mean the entry has been updated since its last publishing.
        if ($deliveryEntry && $previewEntry->getUpdatedAt() != $deliveryEntry->getUpdatedAt()) {
            $previewEntry->pendingChanges = true;
        }

        // We need a static methods map for accessing related entries.
        // If we don't have a method configured for the current content type, let's bail.
        if (!isset($this->linkedEntriesMethods[$previewEntry->getContentType()->getId()])) {
            return;
        }

        $method = $this->linkedEntriesMethods[$previewEntry->getContentType()->getId()];
        $linkedPreviewEntries = $previewEntry->$method();

        foreach ($linkedPreviewEntries as $index => $linkedPreviewEntry) {
            $this->attachEntryState($linkedPreviewEntry, $deliveryEntries, $depth - 1);

            // State bubbles up: if a child entry is in draft state,
            // we mark the parent as being in draft too. Same with pending changes.
            // We use the null coalescing operator because if we've reached the maximum nesting,
            // $linkedPreviewEntry will not have the properties "draft" and "pendingChanges" set.
            $previewEntry->draft = $previewEntry->draft || ($linkedPreviewEntry->draft ?? false);
            $previewEntry->pendingChanges = $previewEntry->pendingChanges || ($linkedPreviewEntry->pendingChanges ?? false);
        }
    }
}
