<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2015-2018 Contentful GmbH
 * @license   MIT
 */

declare(strict_types=1);

namespace App\Tests\Controller;

class ImprintControllerTest extends AppWebTestCase
{
    public function testSettingsPage()
    {
        $this->visit('GET', '/imprint');

        $this->assertBreadcrumb([
            ['Home', '/'],
            ['Imprint', '/imprint'],
        ]);

        $this->assertPageContains('h1', 'Imprint');
        $this->assertPageContains('.main__content table');
    }
}
