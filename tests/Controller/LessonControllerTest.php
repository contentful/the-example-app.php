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

class LessonControllerTest extends AppWebTestCase
{
    public function testLessonsPage()
    {
        $this->visit('GET', '/courses/hello-contentful/lessons', 301);

        $this->assertInstanceOf(RedirectResponse::class, $this->response);
        $this->assertSame('http://localhost/courses/hello-contentful', $this->response->getTargetUrl());
    }

    public function testLessonPage()
    {
        $requestTime = \time();
        $this->visit('GET', '/courses/hello-contentful/lessons/apis');

        $this->assertBreadcrumb([
            ['Home', '/'],
            ['Courses', '/courses'],
            ['Hello Contentful', '/courses/hello-contentful'],
            ['Lessons', '/courses/hello-contentful'],
            ['APIs', '/courses/hello-contentful/lessons/apis'],
        ]);

        $this->assertPageContains('.lesson__title', 'APIs');
        $this->assertPageContains('.table-of-contents__link.active', 'APIs');
        $this->assertPageContains('.lesson__cta', 'Go to the next lesson');

        $visitedLessonsCookie = $this->response->headers->getCookies()[0];
        $this->assertSame('visitedLessons', $visitedLessonsCookie->getName());
        $this->assertCount(1, \json_decode($visitedLessonsCookie->getValue()));
        $this->assertBetween($requestTime + 172800, $visitedLessonsCookie->getExpiresTime(), \time() + 172800);
    }

    public function testLesson404Page()
    {
        $this->visit('GET', '/courses/hello-contentful/lessons/wrong-lesson', 404);
    }

    public function testLessonPageEditorialFeatures()
    {
        $this->visit('GET', '/courses/hello-contentful/lessons/apis?editorial_features=enabled', 302);

        // Two redirects are used:
        // one to the settings page, and one back to the previous URL.
        $this->followRedirect();
        $this->followRedirect();

        $this->assertPageContains('.lesson .editorial-features__item a', 'Edit in the Contentful web app');
    }

    public function testLessonPageGerman()
    {
        $requestTime = \time();
        $this->visit('GET', '/courses/hello-contentful/lessons/apis?locale=de-DE');

        $this->assertPageContainsAttr('.header__logo-link', 'href', '/?locale=de-DE');
        $this->assertPageContains('.lesson__title', 'APIs');
        $this->assertPageContains('.table-of-contents__link.active', 'APIs');
        $this->assertPageContains('.lesson__cta', 'Nächste Lektion ansehen');
        $this->assertPageContainsAttr('.lesson__cta', 'href', '/courses/hello-contentful/lessons/content-model?locale=de-DE');

        $visitedLessonsCookie = $this->response->headers->getCookies()[0];
        $this->assertSame('visitedLessons', $visitedLessonsCookie->getName());
        $this->assertCount(1, \json_decode($visitedLessonsCookie->getValue()));
        $this->assertBetween($requestTime + 172800, $visitedLessonsCookie->getExpiresTime(), \time() + 172800);
    }

    public function testLessonNotFound()
    {
        $this->visit('GET', '/courses/hello-contentful/lessons/not-found', 404);

        $this->assertPageContains('body', 'The lesson you are trying to open does not exist.');
    }
}
