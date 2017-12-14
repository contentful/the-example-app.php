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

/**
 * EntryStateChecker class.
 *
 * This class is responsible for applying state properties
 * to entries retrieved from Contentful.
 * Given an array of entries from the Preview API, it will query
 * the Delivery API and compute the differences.
 */
class EntryStateChecker
{
    /**
     * @var Client
     */
    private $client;

    /**
     * A map of methods for accessing linked entries
     * based on the entry content type.
     *
     * @var string[]
     */
    private static $linkedEntriesMethods = [
        'layout' => 'getContentModules',
        'course' => 'getLessons',
        'lesson' => 'getModules',
    ];

    /**
     * @param ClientFactory $clientFactory
     */
    public function __construct(ClientFactory $clientFactory)
    {
        $this->client = $clientFactory->createClient(Contentful::API_DELIVERY);
    }

    /**
     * @param DynamicEntry[] $entries
     * @param int            $depth
     */
    public function computeState(array $entries, int $depth): void
    {
        if (!\array_filter($entries)) {
            return;
        }

        $deliveryEntries = $this->fetchDeliveryEntries($entries);

        foreach ($entries as $entry) {
            $this->attachEntryState($entry, $deliveryEntries, $depth);
        }
    }

    /**
     * Extracts the meaningful IDs for the given preview entries (including nested ones),
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
        foreach ($this->client->getEntries($query) as $entry) {
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
            $method = self::$linkedEntriesMethods[$entry->getContentType()->getId()] ?? null;

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
     */
    private function attachEntryState(DynamicEntry $previewEntry, array $deliveryEntries, int $depth = 1): void
    {
        // Bail early if we've reached the limit of configured nesting.
        if ($depth === 0) {
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
        // We format the values to remove milliseconds in order to ignore slight discrepancies.
        $previewUpdatedAt = $previewEntry->getUpdatedAt()->format('Y-m-d H:i:s');
        $deliveryUpdatedAt = $deliveryEntry->getUpdatedAt()->format('Y-m-d H:i:s');
        if ($deliveryEntry && $previewUpdatedAt !== $deliveryUpdatedAt) {
            $previewEntry->pendingChanges = true;
        }

        // We need a static methods map for accessing related entries.
        // If we don't have a method configured for the current content type, let's bail.
        if (!isset(self::$linkedEntriesMethods[$previewEntry->getContentType()->getId()])) {
            return;
        }

        $method = self::$linkedEntriesMethods[$previewEntry->getContentType()->getId()];
        $linkedPreviewEntries = $previewEntry->$method();

        foreach ($linkedPreviewEntries as $linkedPreviewEntry) {
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
