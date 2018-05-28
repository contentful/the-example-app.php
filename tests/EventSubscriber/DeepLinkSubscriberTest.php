<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2017-2018 Contentful GmbH
 * @license   MIT
 */

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\Tests\Controller\AppWebTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;

class DeepLinkSubscriberTest extends AppWebTestCase
{
    public function testRedirectFull()
    {
        $this->visit('GET', '/?space_id=cfexampleapi&delivery_token=b4c0n73n7fu1&preview_token=e5e8d4c5c122cf28fc1af3ff77d28bef78a3952957f15067bbc29f2f0dde0b50&api=cda&locale=en-US&editorial_features=enabled', 302);

        // First, the redirect to the settings page/
        $this->assertInstanceOf(RedirectResponse::class, $this->response);
        $this->assertSame('/settings', $this->response->getTargetUrl());

        // Second, the redirect back to the original URL.
        $this->followRedirect();
        $this->assertInstanceOf(RedirectResponse::class, $this->response);
        $this->assertSame('/?api=cda&locale=en-US', $this->response->getTargetUrl());

        $cookie = $this->response->headers->getCookies()[0];
        $this->assertSame('theExampleAppSettings', $cookie->getName());
        $this->assertSame('{"spaceId":"cfexampleapi","deliveryToken":"b4c0n73n7fu1","previewToken":"e5e8d4c5c122cf28fc1af3ff77d28bef78a3952957f15067bbc29f2f0dde0b50","editorialFeatures":true}', $cookie->getValue());
    }

    public function testRedirectCredentials()
    {
        $this->visit('GET', '/?space_id=cfexampleapi&delivery_token=b4c0n73n7fu1&preview_token=e5e8d4c5c122cf28fc1af3ff77d28bef78a3952957f15067bbc29f2f0dde0b50', 302);

        // First, the redirect to the settings page/
        $this->assertInstanceOf(RedirectResponse::class, $this->response);
        $this->assertSame('/settings', $this->response->getTargetUrl());

        // Second, the redirect back to the original URL.
        $this->followRedirect();
        $this->assertInstanceOf(RedirectResponse::class, $this->response);
        $this->assertSame('/', $this->response->getTargetUrl());

        $cookie = $this->response->headers->getCookies()[0];
        $this->assertSame('theExampleAppSettings', $cookie->getName());
        $this->assertSame('{"spaceId":"cfexampleapi","deliveryToken":"b4c0n73n7fu1","previewToken":"e5e8d4c5c122cf28fc1af3ff77d28bef78a3952957f15067bbc29f2f0dde0b50","editorialFeatures":false}', $cookie->getValue());
    }

    public function testRedirectEditorialFeatures()
    {
        $this->visit('GET', '/?editorial_features=enabled', 302);

        // First, the redirect to the settings page/
        $this->assertInstanceOf(RedirectResponse::class, $this->response);
        $this->assertSame('/settings', $this->response->getTargetUrl());

        // Second, the redirect back to the original URL.
        $this->followRedirect();
        $this->assertInstanceOf(RedirectResponse::class, $this->response);
        $this->assertSame('/', $this->response->getTargetUrl());

        $cookie = $this->response->headers->getCookies()[0];
        $this->assertSame('theExampleAppSettings', $cookie->getName());

        // Let's use the actual default credentials to make sure
        // the event subscriber has not altered them.
        $credentials = static::bootKernel()
            ->getContainer()
            ->getParameter('default_credentials');
        $this->assertJsonStringEqualsJsonString(\json_encode([
            'editorialFeatures' => true,
            'spaceId' => $credentials['space_id'],
            'deliveryToken' => $credentials['delivery_token'],
            'previewToken' => $credentials['preview_token'],
        ]), $cookie->getValue());
    }
}
