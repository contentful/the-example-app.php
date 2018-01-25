<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2017 Contentful GmbH
 * @license   MIT
 */
declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\Tests\Controller\AppWebTestCase;

class LocaleSubscriberTest extends AppWebTestCase
{
    public function testLocaleDetection()
    {
        $this->visit('GET', '/?locale=en-US');

        $this->assertSame('en-US', $this->request->getLocale());
        $this->assertPageContains('.module-highlighted-course__title', 'Hello Contentful');

        $this->visit('GET', '/?locale=de-DE');

        $this->assertSame('de-DE', $this->request->getLocale());
        $this->assertPageContains('.module-highlighted-course__title', 'Hallo Contentful');
    }

    public function testInvalidLocale()
    {
        $this->visit('GET', '/?locale=it-IT', 500);

        $this->assertPageContains('body', 'Unknown locale: it-IT');
    }
}
