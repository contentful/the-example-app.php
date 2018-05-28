<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2017-2018 Contentful GmbH
 * @license   MIT
 */

namespace App\Service;

use Contentful\Delivery\Resource\Environment;
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
     * @var State
     */
    private $state;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var string[]
     */
    private $availableLocales;

    /**
     * @param State      $state
     * @param Contentful $contentful
     * @param string[]   $availableLocales
     */
    public function __construct(State $state, Contentful $contentful, array $availableLocales)
    {
        $this->state = $state;
        $this->environment = $contentful->findEnvironment();
        $this->availableLocales = $availableLocales;
    }

    /**
     * @return ContentfulLocale[]
     */
    public function getAll(): array
    {
        return $this->environment->getLocales();
    }

    /**
     * @return ContentfulLocale|null
     */
    public function getCurrent(): ?ContentfulLocale
    {
        try {
            return $this->environment->getLocale($this->state->getLocale());
        } catch (\InvalidArgumentException $exception) {
            return null;
        }
    }

    /**
     * @return string[]
     */
    public function getAvailable(): array
    {
        return $this->availableLocales;
    }
}
