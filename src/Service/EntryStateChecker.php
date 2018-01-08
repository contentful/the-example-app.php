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
     * @param ClientFactory $clientFactory
     */
    public function __construct(ClientFactory $clientFactory)
    {
        $this->client = $clientFactory->createClient(Contentful::API_DELIVERY);
    }

    /**
     * @param DynamicEntry[] $entries
     * @param string         $method  The method for accessing linked entries
     */
    public function computeState(array $entries, string $method): void
    {
        $deliveryEntries = $this->fetchDeliveryEntries($entries, $method);

        foreach ($entries as $entry) {
            $this->attachEntryState($entry, $deliveryEntries, $method);
        }
    }

    /**
     * Extracts the meaningful IDs for the given preview entries (including nested ones),
     * and then queries the Delivery API in order to get a list with the corresponding,
     * published entries.
     *
     * @param DynamicEntry[] $entries
     * @param string         $method
     *
     * @return DynamicEntry[]
     */
    private function fetchDeliveryEntries(array $entries, string $method): array
    {
        $ids = $this->extractIdsForComparison($entries, $method);
        $query = (new Query())
            ->setInclude(0)
            ->where('sys.id', $ids, 'in');

        $deliveryEntries = [];
        foreach ($this->client->getEntries($query) as $entry) {
            $deliveryEntries[$entry->getId()] = $entry;
        }

        return $deliveryEntries;
    }

    /**
     * Given an array of entries, it will extract all IDs including from the nested ones.
     *
     * @param DynamicEntry[] $entries
     * @param string         $method
     *
     * @return string[]
     */
    private function extractIdsForComparison(array $entries, string $method): array
    {
        $ids = [];
        foreach ($entries as $entry) {
            $ids[] = $entry->getId();

            foreach ($entry->$method() as $linkedEntry) {
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
     * @param DynamicEntry   $previewEntry
     * @param DynamicEntry[] $deliveryEntries An array where the entry ID is used as key
     * @param string         $method          The method used for calling the linked entries
     */
    private function attachEntryState(DynamicEntry $previewEntry, array $deliveryEntries, string $method): void
    {
        $state = $this->compare($previewEntry, $deliveryEntries);

        $previewEntry->draft = $state['draft'];
        $previewEntry->pendingChanges = $state['pendingChanges'];

        foreach ($previewEntry->$method() as $linkedPreviewEntry) {
            $state = $this->compare($linkedPreviewEntry, $deliveryEntries);

            $previewEntry->draft = $previewEntry->draft || $state['draft'];
            $previewEntry->pendingChanges = $previewEntry->pendingChanges || $state['pendingChanges'];
        }
    }

    /**
     * Compares a preview entry with the one found in a list of given delivery entries,
     * and returns an array with the resulting state.
     *
     * @param DynamicEntry $previewEntry
     * @param array        $deliveryEntries
     *
     * @return bool[]
     */
    private function compare(DynamicEntry $previewEntry, array $deliveryEntries): array
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
        $previewUpdatedAt = $previewEntry->getUpdatedAt()->format('Y-m-d H:i:s');
        $deliveryUpdatedAt = $deliveryEntry ? $deliveryEntry->getUpdatedAt()->format('Y-m-d H:i:s') : null;
        if ($deliveryEntry && $previewUpdatedAt !== $deliveryUpdatedAt) {
            $state['pendingChanges'] = true;
        }

        return $state;
    }
}
