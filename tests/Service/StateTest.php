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

        $this->assertEquals([
            'spaceId' => 'defaultSpaceId',
            'deliveryToken' => 'defaultDeliveryToken',
            'previewToken' => 'defaultPreviewToken',
            'editorialFeatures' => false,
        ], $state->getSettings());
        $this->assertEquals('defaultSpaceId', $state->getSpaceId());
        $this->assertEquals('defaultDeliveryToken', $state->getDeliveryToken());
        $this->assertEquals('defaultPreviewToken', $state->getPreviewToken());
        $this->assertFalse($state->hasEditorialFeaturesEnabled());
        $this->assertFalse($state->usesCookieCredentials());
        $this->assertEquals('cda', $state->getApi());
        $this->assertEquals('Content Delivery API', $state->getApiLabel());
        $this->assertTrue($state->isDeliveryApi());
        $this->assertEquals('en-US', $state->getLocale());
        $this->assertEquals(['en-US', 'de-DE'], $state->getAvailableLocales());
        $this->assertEquals('', $state->getQueryString());
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

        $this->assertEquals([
            'spaceId' => 'cookieSpaceId',
            'deliveryToken' => 'cookieDeliveryToken',
            'previewToken' => 'cookiePreviewToken',
            'editorialFeatures' => true,
        ], $state->getSettings());
        $this->assertEquals('cookieSpaceId', $state->getSpaceId());
        $this->assertEquals('cookieDeliveryToken', $state->getDeliveryToken());
        $this->assertEquals('cookiePreviewToken', $state->getPreviewToken());
        $this->assertTrue($state->hasEditorialFeaturesEnabled());
        $this->assertTrue($state->usesCookieCredentials());
        $this->assertEquals('cpa', $state->getApi());
        $this->assertEquals('Content Preview API', $state->getApiLabel());
        $this->assertFalse($state->isDeliveryApi());
        $this->assertEquals('de-DE', $state->getLocale());
        $this->assertEquals(['en-US', 'de-DE'], $state->getAvailableLocales());
        $this->assertEquals('?api=cpa&locale=de-DE', $state->getQueryString());
    }
}
