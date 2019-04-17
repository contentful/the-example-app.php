<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2015-2018 Contentful GmbH
 * @license   MIT
 */

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Breadcrumb.
 *
 * This class is used to store information about the breadcrumb of the current page.
 * It provides easy access to url generation and label translation.
 * It is also automatically injected in all templates, using the variable "breadcrumb".
 */
class Breadcrumb
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var State
     */
    private $state;

    /**
     * @var string[]
     */
    private $items = [];

    /**
     * @param TranslatorInterface   $translator
     * @param UrlGeneratorInterface $urlGenerator
     * @param State                 $state
     */
    public function __construct(TranslatorInterface $translator, UrlGeneratorInterface $urlGenerator, State $state)
    {
        $this->translator = $translator;
        $this->urlGenerator = $urlGenerator;
        $this->state = $state;
    }

    /**
     * @param string $label
     * @param string $route
     * @param array  $parameters
     * @param bool   $translate
     *
     * @return self
     */
    public function add(string $label, string $route, array $parameters = [], bool $translate = true): self
    {
        $label = $translate ? $this->translator->trans($label) : $label;
        $url = $this->urlGenerator->generate($route, $parameters);

        $this->items[] = [
            'label' => $label,
            'url' => $url.$this->state->getQueryString(),
        ];

        return $this;
    }

    /**
     * @return string[]
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
