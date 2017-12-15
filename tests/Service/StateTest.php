<?php

/**
 * This file is part of the contentful/the-example-app.php package.
 *
 * @copyright 2015-2017 Contentful GmbH
 * @license   MIT
 */
declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\State;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class StateTest extends TestCase
{
    public function testGettersWithoutCookieAndQueryParameters()
    {
        $state = new State(null, 'defaultSpaceId', 'defaultDeliveryToken', 'defaultPreviewToken', 'en-US', ['en-US', 'de-DE']);

        $this->assertSame([
            'spaceId' => 'defaultSpaceId',
            'deliveryToken' => 'defaultDeliveryToken',
            'previewToken' => 'defaultPreviewToken',
            'editorialFeatures' => false,
        ], $state->getSettings());
        $this->assertSame('defaultSpaceId', $state->getSpaceId());
        $this->assertSame('defaultDeliveryToken', $state->getDeliveryToken());
        $this->assertSame('defaultPreviewToken', $state->getPreviewToken());
        $this->assertFalse($state->hasEditorialFeaturesEnabled());
        $this->assertFalse($state->usesCookieCredentials());
        $this->assertSame('cda', $state->getApi());
        $this->assertSame('Content Delivery API', $state->getApiLabel());
        $this->assertTrue($state->isDeliveryApi());
        $this->assertSame('en-US', $state->getLocale());
        $this->assertSame(['en-US', 'de-DE'], $state->getAvailableLocales());
        $this->assertSame('', $state->getQueryString());
    }

    public function testGettersWithCookieAndQueryParameters()
    {
        $cookie = '{"spaceId": "cookieSpaceId", "deliveryToken": "cookieDeliveryToken", "previewToken": "cookiePreviewToken", "editorialFeatures": true}';
        $request = new Request(
            ['api' => 'cpa', 'locale' => 'de-DE'],
            [],
            [],
            ['theExampleAppSettings' => $cookie]
        );

        $state = new State($request, 'defaultSpaceId', 'defaultDeliveryToken', 'defaultPreviewToken', 'en-US', ['en-US', 'de-DE']);

        $this->assertSame([
            'spaceId' => 'cookieSpaceId',
            'deliveryToken' => 'cookieDeliveryToken',
            'previewToken' => 'cookiePreviewToken',
            'editorialFeatures' => true,
        ], $state->getSettings());
        $this->assertSame('cookieSpaceId', $state->getSpaceId());
        $this->assertSame('cookieDeliveryToken', $state->getDeliveryToken());
        $this->assertSame('cookiePreviewToken', $state->getPreviewToken());
        $this->assertTrue($state->hasEditorialFeaturesEnabled());
        $this->assertTrue($state->usesCookieCredentials());
        $this->assertSame('cpa', $state->getApi());
        $this->assertSame('Content Preview API', $state->getApiLabel());
        $this->assertFalse($state->isDeliveryApi());
        $this->assertSame('de-DE', $state->getLocale());
        $this->assertSame(['en-US', 'de-DE'], $state->getAvailableLocales());
        $this->assertSame('?api=cpa&locale=de-DE', $state->getQueryString());
    }
}
