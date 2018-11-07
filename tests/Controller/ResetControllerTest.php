<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2015-2018 Contentful GmbH
 * @license   MIT
 */

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;

class ResetControllerTest extends AppWebTestCase
{
    public function testCookieReset()
    {
        $this->visit('POST', '/settings/reset', 302);

        $this->assertInstanceOf(RedirectResponse::class, $this->response);
        $this->assertSame('/settings', $this->response->getTargetUrl());

        $cookie = $this->response->headers->getCookies()[0];
        $this->assertSame('theExampleAppSettings', $cookie->getName());
        $this->assertSame(1, $cookie->getExpiresTime());
    }
}
