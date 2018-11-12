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

class CoursesControllerTest extends AppWebTestCase
{
    public function testCoursesPage()
    {
        $this->visit('GET', '/courses');

        $this->assertBreadcrumb([
            ['Home', '/'],
            ['Courses', '/courses'],
        ]);

        $this->assertPageContains('h1', 'All courses');
        $this->assertPageContains('.course-card');
        $this->assertPageContains('.layout-sidebar__sidebar-title', 'Categories');
        $this->assertPageContains('.sidebar-menu__link.active', 'All courses');
    }

    public function testCategoriesPage()
    {
        $this->visit('GET', '/courses/categories', 301);

        $this->assertInstanceOf(RedirectResponse::class, $this->response);
        $this->assertSame('http://localhost/courses', $this->response->getTargetUrl());
    }

    public function testCategoryPage()
    {
        $this->visit('GET', '/courses/categories/getting-started');

        $this->assertBreadcrumb([
            ['Home', '/'],
            ['Courses', '/courses'],
            ['Getting started', '/courses/categories/getting-started'],
        ]);

        $this->assertPageContains('h1', 'Getting started');
        $this->assertPageContains('.course-card');
        $this->assertPageContains('.layout-sidebar__sidebar-title', 'Categories');
        $this->assertPageContains('.sidebar-menu__link.active', 'Getting started');
    }

    public function testCategory404Page()
    {
        $this->visit('GET', '/courses/categories/wrong-category', 404);

        $this->assertPageContains('body', 'The category you are trying to open does not exist.');
    }

    public function testCategoryPageEditorialFeatures()
    {
        $this->visit('GET', '/courses/categories/getting-started?editorial_features=enabled', 302);

        // Two redirects are used:
        // one to the settings page, and one back to the previous URL.
        $this->followRedirect();
        $this->followRedirect();

        $this->assertPageContains('.courses .editorial-features__item a', 'Edit in the Contentful web app');
    }

    public function testCategoryPageGerman()
    {
        $this->visit('GET', '/courses/categories/getting-started?locale=de-DE');

        $this->assertPageContainsAttr('.header__logo-link', 'href', '/?locale=de-DE');
        $this->assertPageContains('h1', 'Einstiegskurs');
        $this->assertPageContains('.layout-sidebar__sidebar-title', 'Kategorien');
        $this->assertPageContains('.sidebar-menu__link.active', 'Einstiegskurs');
    }
}
