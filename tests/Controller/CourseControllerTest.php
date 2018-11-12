<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2015-2018 Contentful GmbH
 * @license   MIT
 */

declare(strict_types=1);

namespace App\Tests\Controller;

class CourseControllerTest extends AppWebTestCase
{
    public function testCoursePage()
    {
        $this->visit('GET', '/courses/hello-contentful');

        $this->assertBreadcrumb([
            ['Home', '/'],
            ['Courses', '/courses'],
            ['Hello Contentful', '/courses/hello-contentful'],
        ]);

        $this->assertPageContains('.layout-sidebar__sidebar-title', 'Table of contents');
        $this->assertPageContains('.course__title', 'Hello Contentful');
        $this->assertPageContains('.course__overview-cta', 'Start course');
        $this->assertPageContains('.table-of-contents__link.active', 'Course overview');
    }

    public function testCourse404Page()
    {
        $this->visit('GET', '/courses/wrong-course', 404);
    }

    public function testCoursePageEditorialFeatures()
    {
        $this->visit('GET', '/courses/hello-contentful?editorial_features=enabled', 302);

        // Two redirects are used:
        // one to the settings page, and one back to the previous URL.
        $this->followRedirect();
        $this->followRedirect();

        $this->assertPageContains('.course .editorial-features__item a', 'Edit in the Contentful web app');
    }

    public function testCoursePageGerman()
    {
        $this->visit('GET', '/courses/hello-contentful?locale=de-DE');

        $this->assertPageContainsAttr('.header__logo-link', 'href', '/?locale=de-DE');
        $this->assertPageContains('.layout-sidebar__sidebar-title', 'Inhalt');
        $this->assertPageContains('.course__title', 'Hallo Contentful');
        $this->assertPageContains('.course__overview-cta', 'Kurs beginnen');
        $this->assertPageContains('.table-of-contents__link.active', 'Kurs Übersicht');
    }

    public function testCourseNotFound()
    {
        $this->visit('GET', '/courses/not-found', 404);

        $this->assertPageContains('body', 'The course you are trying to open does not exist.');
    }
}
