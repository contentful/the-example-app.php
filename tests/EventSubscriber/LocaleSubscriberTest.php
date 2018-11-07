<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2015-2018 Contentful GmbH
 * @license   MIT
 */

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\Tests\Controller\AppWebTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;

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

    /**
     * @dataProvider invalidLocaleProvider
     */
    public function testInvalidLocale(string $requestUrl, string $redirectUrl)
    {
        $this->visit('GET', $requestUrl, 302);

        $this->assertInstanceOf(RedirectResponse::class, $this->response);
        $this->assertSame($redirectUrl, $this->response->getTargetUrl());
    }

    public function invalidLocaleProvider()
    {
        return [
            ['/?locale=foobar', '/'],
            ['/?api=cda&locale=foobar', '/'],
            ['/?api=cpa&locale=foobar', '/?api=cpa'],
            ['/courses/hello-contentful/lessons/apis?locale=foobar', '/courses/hello-contentful/lessons/apis'],
            ['/courses/hello-contentful/lessons/apis?api=cda&locale=foobar', '/courses/hello-contentful/lessons/apis'],
            ['/courses/hello-contentful/lessons/apis?api=cpa&locale=foobar', '/courses/hello-contentful/lessons/apis?api=cpa'],
        ];
    }
}
