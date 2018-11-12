<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2015-2018 Contentful GmbH
 * @license   MIT
 */

declare(strict_types=1);

namespace App\Twig;

use League\CommonMark\CommonMarkConverter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * AppExtension.
 *
 * There are other Twig extensions or Symfony bundles that do this.
 * However, our use case is extremely simple, so we make use of Symfony 4's
 * autowiring to configure a very simple Twig filter for converting
 * Markdown into HTML.
 * In production environments, it would be a good idea to implement
 * some sort of caching of the rendered HTML. In this app, we're dealing
 * with rather short text contents, so the performance impact is negligible.
 */
class AppExtension extends AbstractExtension
{
    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('markdown', [$this, 'convertToHtml'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Converts Markdown text to HTML.
     *
     * @param string $markdown
     *
     * @return string
     */
    public function convertToHtml(string $markdown): string
    {
        $converter = new CommonMarkConverter();

        return $converter->convertToHtml($markdown);
    }
}
