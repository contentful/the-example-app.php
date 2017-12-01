<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2017 Contentful GmbH
 * @license   MIT
 */
declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;

class SettingsControllerTest extends AppWebTestCase
{
    public function testSettingsPage()
    {
        $this->visit('GET', '/settings');

        $this->assertBreadcrumb([
            ['Home', '/'],
            ['Settings', '/settings'],
        ]);

        $this->assertPageContains('h1', 'Settings');
    }

    public function testSettingsSubmitForm()
    {
        $this->visit('GET', '/settings');

        $button = $this->crawler->selectButton('Save settings');

        $form = $button->form([
            'settings[spaceId]' => 'cfexampleapi',
            'settings[deliveryToken]' => 'b4c0n73n7fu1',
            'settings[previewToken]' => 'e5e8d4c5c122cf28fc1af3ff77d28bef78a3952957f15067bbc29f2f0dde0b50',
        ]);
        $form['settings[editorialFeatures]']->tick();

        $requestTime = \time();
        $this->client->submit($form);

        $response = $this->client->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/settings', $response->getTargetUrl());

        $credentialsCookie = $response->headers->getCookies()[0];
        $this->assertEquals('theExampleAppSettings', $credentialsCookie->getName());
        $this->assertEquals('{"spaceId":"cfexampleapi","deliveryToken":"b4c0n73n7fu1","previewToken":"e5e8d4c5c122cf28fc1af3ff77d28bef78a3952957f15067bbc29f2f0dde0b50","editorialFeatures":true}', $credentialsCookie->getValue());
        $this->assertBetween($requestTime + 172800, $credentialsCookie->getExpiresTime(), \time() + 172800);

        $this->crawler = $this->client->followRedirect();

        $this->assertPageContains('.status-block--success .status-block__title', 'Changes saved successfully!');
    }
}
