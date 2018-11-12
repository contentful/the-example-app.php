<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2015-2018 Contentful GmbH
 * @license   MIT
 */

declare(strict_types=1);

namespace App\Tests\Twig;

use App\Twig\AppExtension;
use PHPUnit\Framework\TestCase;

class AppExtensionTest extends TestCase
{
    /**
     * @dataProvider markdownProvider
     */
    public function testMarkdownFilter(string $markdown, string $html)
    {
        $extension = new AppExtension();

        $this->assertSame($html, $extension->convertToHtml($markdown));
    }

    public static function markdownProvider()
    {
        return [
            ['[Link](https://www.example.com)', '<p><a href="https://www.example.com">Link</a></p>'."\n"],
            ['# Some title', '<h1>Some title</h1>'."\n"],
            ['![Alternative text](https://www.example.com/picture.jpg "Picture title")', '<p><img src="https://www.example.com/picture.jpg" alt="Alternative text" title="Picture title" /></p>'."\n"],
            ['`echo "Hello, world!";`', '<p><code>echo &quot;Hello, world!&quot;;</code></p>'."\n"],
        ];
    }
}
