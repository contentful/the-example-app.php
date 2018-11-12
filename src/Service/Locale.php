<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2015-2018 Contentful GmbH
 * @license   MIT
 */

declare(strict_types=1);

namespace App\Service;

use Contentful\Delivery\Resource\Locale as ContentfulLocale;

/**
 * Locale class.
 *
 * Convenience class for handling locale information.
 * This is mainly used in templates for working with locales
 * retrieved from the API.
 */
class Locale
{
    /**
     * @var ContentfulLocale[]
     */
    private $contentfulLocales = [];

    /**
     * @var ContentfulLocale|null
     */
    private $current;

    /**
     * @param State      $state
     * @param Contentful $contentful
     */
    public function __construct(State $state, Contentful $contentful)
    {
        try {
            $environment = $contentful->findEnvironment();

            $this->contentfulLocales = $environment->getLocales();
            $this->current = $environment->getLocale($state->getLocale());
        } catch (\Exception $exception) {
        }
    }

    /**
     * @return ContentfulLocale[]
     */
    public function getAll(): array
    {
        return $this->contentfulLocales;
    }

    /**
     * @return ContentfulLocale|null
     */
    public function getCurrent(): ?ContentfulLocale
    {
        return $this->current;
    }
}
