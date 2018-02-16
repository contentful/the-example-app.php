<?php

namespace App\Service;

use Contentful\Delivery\Locale as ContentfulLocale;
use Contentful\Delivery\Space;

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
     * @var Space
     */
    private $space;

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
        $this->space = $contentful->findSpace();
        $this->availableLocales = $availableLocales;
    }

    /**
     * @return ContentfulLocale[]
     */
    public function getAll(): array
    {
        return $this->space->getLocales();
    }

    /**
     * @return ContentfulLocale|null
     */
    public function getCurrent(): ?ContentfulLocale
    {
        try {
            return $this->space->getLocale($this->state->getLocale());
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
