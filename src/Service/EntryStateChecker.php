<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2015-2018 Contentful GmbH
 * @license   MIT
 */

declare(strict_types=1);

namespace App\Service;

use Contentful\Delivery\Client;
use Contentful\Delivery\Query;
use Contentful\Delivery\Resource\Entry;

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
     * @param ClientFactory $clientFactory
     */
    public function __construct(ClientFactory $clientFactory)
    {
        $this->client = $clientFactory->createClient(Contentful::API_DELIVERY);
    }

    /**
     * Compares entries retrieved from the Preview API
     * to their equivalent in the Delivery API.
     * If the entries have a property "Entry[] children",
     * it will be checked and state will bubble up from them.
     *
     * @param Entry[] $entries
     */
    public function computeState(Entry ...$entries): void
    {
        $deliveryEntries = $this->fetchDeliveryEntries($entries);

        foreach ($entries as $entry) {
            $this->attachEntryState($entry, $deliveryEntries);
        }
    }

    /**
     * Extracts the meaningful IDs for the given preview entries (including nested ones),
     * and then queries the Delivery API in order to get a list with the corresponding,
     * published entries.
     *
     * @param Entry[] $entries
     *
     * @return Entry[]
     */
    private function fetchDeliveryEntries(array $entries): array
    {
        $ids = $this->extractIdsForComparison($entries);
        $query = (new Query())
            ->setInclude(0)
            ->where('sys.id[in]', $ids)
        ;

        $deliveryEntries = [];
        foreach ($this->client->getEntries($query) as $entry) {
            $deliveryEntries[$entry->getId()] = $entry;
        }

        return $deliveryEntries;
    }

    /**
     * Given an array of entries, it will extract all IDs including from the nested ones.
     *
     * @param Entry[] $entries
     *
     * @return string[]
     */
    private function extractIdsForComparison(array $entries): array
    {
        $ids = [];
        foreach ($entries as $entry) {
            $ids[] = $entry->getId();

            try {
                $children = $entry->children;
            } catch (\InvalidArgumentException $exception) {
                $children = [];
            }
            foreach ($children as $linkedEntry) {
                $ids[] = $linkedEntry->getId();
            }
        }

        return $ids;
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
     * @param Entry   $previewEntry
     * @param Entry[] $deliveryEntries An array where the entry ID is used as key
     */
    private function attachEntryState(Entry $previewEntry, array $deliveryEntries): void
    {
        $state = $this->compare($previewEntry, $deliveryEntries);

        $previewEntry->draft = $state['draft'];
        $previewEntry->pendingChanges = $state['pendingChanges'];

        try {
            $children = $previewEntry->children;
        } catch (\InvalidArgumentException $exception) {
            $children = [];
        }
        foreach ($children as $linkedPreviewEntry) {
            $state = $this->compare($linkedPreviewEntry, $deliveryEntries);

            $previewEntry->draft = $previewEntry->draft || $state['draft'];
            $previewEntry->pendingChanges = $previewEntry->pendingChanges || $state['pendingChanges'];
        }
    }

    /**
     * Compares a preview entry with the one found in a list of given delivery entries,
     * and returns an array with the resulting state.
     *
     * @param Entry $previewEntry
     * @param array $deliveryEntries
     *
     * @return bool[]
     */
    private function compare(Entry $previewEntry, array $deliveryEntries): array
    {
        $state = [
            'draft' => false,
            'pendingChanges' => false,
        ];

        $deliveryEntry = $deliveryEntries[$previewEntry->getId()] ?? null;

        // If no entry is found, it means it's hasn't been published yet.
        if (!$deliveryEntry) {
            $state['draft'] = true;
        }

        // Different updatedAt values mean the entry has been updated since its last publishing.
        // We format the values to remove milliseconds in order to ignore slight discrepancies.
        $previewUpdatedAt = $previewEntry->getSystemProperties()->getUpdatedAt()->format('Y-m-d H:i:s');
        $deliveryUpdatedAt = $deliveryEntry ? $deliveryEntry->getSystemProperties()->getUpdatedAt()->format('Y-m-d H:i:s') : null;
        if ($deliveryEntry && $previewUpdatedAt !== $deliveryUpdatedAt) {
            $state['pendingChanges'] = true;
        }

        return $state;
    }
}
