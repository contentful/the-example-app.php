<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2015-2018 Contentful GmbH
 * @license   MIT
 */

declare(strict_types=1);

namespace App\Tests\Controller;

class LandingPageControllerTest extends AppWebTestCase
{
    public function testHomepage()
    {
        $this->visit('GET', '/');

        $this->assertPageContains('.module-highlighted-course__title', 'Hello Contentful');
        $this->assertPageContains('.module-highlighted-course__link-wrapper', 'view course');
    }

    public function testHomepageGerman()
    {
        $this->visit('GET', '/?locale=de-DE');

        $this->assertPageContains('.module-highlighted-course__title', 'Hallo Contentful');
        $this->assertPageContains('.module-highlighted-course__link-wrapper', 'Kurs ansehen');
    }

    public function test404Page()
    {
        $this->visit('GET', '/wrong-page', 404);

        $this->assertPageContains('body', 'The page you are trying to open does not exist.');
    }

    public function testPageLayout()
    {
        $this->visit('GET', '/');

        // Meta tags
        $this->assertPageContains('title', 'Home — The Example App');
        $this->assertPageContainsAttr('meta[name="description"]', 'content', 'This is "The Example App", a reference for building your own applications using Contentful.');
        $this->assertPageContainsAttr('meta[name="twitter:card"]', 'content', 'This is "The Example App", a reference for building your own applications using Contentful.');

        // Header navigation
        $this->assertPageContains('.header__navigation.main-navigation .active', 'Home');
        $this->assertPageContains('.header__navigation.main-navigation:not(.active)', 'Courses');

        // Footer navigation
        $this->assertPageContains('.footer__navigation.main-navigation .active', 'Home');
        $this->assertPageContains('.footer__navigation.main-navigation:not(.active)', 'Courses');

        // Footer disclaimer links
        $this->assertPageContains('.footer__disclaimer-text a:nth-of-type(1)', 'View on GitHub');
        $this->assertPageContainsAttr('.footer__disclaimer-text a:nth-of-type(1)', 'href', 'https://github.com/contentful/the-example-app.php');
        $this->assertPageContains('.footer__disclaimer-text a:nth-of-type(2)', 'Imprint');
        $this->assertPageContainsAttr('.footer__disclaimer-text a:nth-of-type(2)', 'href', '/imprint');
        $this->assertPageContains('.footer__disclaimer-text a:nth-of-type(3)', 'Contact us');
        $this->assertPageContainsAttr('.footer__disclaimer-text a:nth-of-type(3)', 'href', 'https://www.contentful.com/contact/');

        // Social links
        $this->assertPageContainsAttr('.footer__social a:nth-of-type(1)', 'href', 'https://www.facebook.com/contentful/');
        $this->assertPageContainsAttr('.footer__social a:nth-of-type(2)', 'href', 'https://twitter.com/contentful');
        $this->assertPageContainsAttr('.footer__social a:nth-of-type(3)', 'href', 'https://github.com/contentful');
    }
}
