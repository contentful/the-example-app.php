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
use Symfony\Component\HttpFoundation\RedirectResponse;

class DeepLinkSubscriberTest extends AppWebTestCase
{
    public function testDeepLinkRedirect()
    {
        $this->visit('GET', '/?space_id=cfexampleapi&delivery_token=b4c0n73n7fu1&preview_token=e5e8d4c5c122cf28fc1af3ff77d28bef78a3952957f15067bbc29f2f0dde0b50&api=cda&locale=en-US&enable_editorial_features', 302);

        $this->assertInstanceOf(RedirectResponse::class, $this->response);
        $this->assertSame('/?api=cda&locale=en-US', $this->response->getTargetUrl());

        $cookie = $this->response->headers->getCookies()[0];
        $this->assertSame('theExampleAppSettings', $cookie->getName());
        $this->assertSame('{"spaceId":"cfexampleapi","deliveryToken":"b4c0n73n7fu1","previewToken":"e5e8d4c5c122cf28fc1af3ff77d28bef78a3952957f15067bbc29f2f0dde0b50","editorialFeatures":true}', $cookie->getValue());
    }
}
