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
        $this->visit('GET', '/courses/hello-contentful?enable_editorial_features');

        $this->assertPageContainsAttr('.header__logo-link', 'href', '/?enable_editorial_features');
        $this->assertPageContainsAttr('.course__overview-cta', 'href', '/courses/hello-contentful/lessons/architecture?enable_editorial_features');
        $this->assertPageContains('.course .editorial-features__item a', 'Edit in the Contentful Web App');
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

    public function testLessonsPage()
    {
        $this->visit('GET', '/courses/hello-contentful/lessons', 301);

        $this->assertInstanceOf(RedirectResponse::class, $this->response);
        $this->assertSame('http://localhost/courses/hello-contentful', $this->response->getTargetUrl());
    }

    public function testLessonPage()
    {
        $requestTime = \time();
        $this->visit('GET', '/courses/hello-contentful/lessons/architecture');

        $this->assertBreadcrumb([
            ['Home', '/'],
            ['Courses', '/courses'],
            ['Hello Contentful', '/courses/hello-contentful'],
            ['Lessons', '/courses/hello-contentful'],
            ['Architecture', '/courses/hello-contentful/lessons/architecture'],
        ]);

        $this->assertPageContains('.lesson__title', 'Architecture');
        $this->assertPageContains('.table-of-contents__link.active', 'Architecture');
        $this->assertPageContains('.lesson__cta', 'Go to the next lesson');

        $visitedLessonsCookie = $this->response->headers->getCookies()[0];
        $this->assertSame('visitedLessons', $visitedLessonsCookie->getName());
        $this->assertCount(1, json_decode($visitedLessonsCookie->getValue()));
        $this->assertBetween($requestTime + 172800, $visitedLessonsCookie->getExpiresTime(), \time() + 172800);
    }

    public function testLesson404Page()
    {
        $this->visit('GET', '/courses/hello-contentful/lessons/wrong-lesson', 404);
    }

    public function testLessonPageEditorialFeatures()
    {
        $this->visit('GET', '/courses/hello-contentful/lessons/architecture?enable_editorial_features');

        $this->assertPageContainsAttr('.header__logo-link', 'href', '/?enable_editorial_features');
        $this->assertPageContains('.lesson .editorial-features__item a', 'Edit in the Contentful Web App');
    }

    public function testLessonPageGerman()
    {
        $requestTime = \time();
        $this->visit('GET', '/courses/hello-contentful/lessons/architecture?locale=de-DE');

        $this->assertPageContainsAttr('.header__logo-link', 'href', '/?locale=de-DE');
        $this->assertPageContains('.lesson__title', 'Architektur');
        $this->assertPageContains('.table-of-contents__link.active', 'Architektur');
        $this->assertPageContains('.lesson__cta', 'Nächste Lektion ansehen');
        $this->assertPageContainsAttr('.lesson__cta', 'href', '/courses/hello-contentful/lessons/content-model?locale=de-DE');

        $visitedLessonsCookie = $this->response->headers->getCookies()[0];
        $this->assertSame('visitedLessons', $visitedLessonsCookie->getName());
        $this->assertCount(1, json_decode($visitedLessonsCookie->getValue()));
        $this->assertBetween($requestTime + 172800, $visitedLessonsCookie->getExpiresTime(), \time() + 172800);
    }
}
