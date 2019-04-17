<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2015-2018 Contentful GmbH
 * @license   MIT
 */

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * CourseController class.
 */
class CourseController extends AppController
{
    /**
     * Renders a course when `/courses/{courseSlug}` is requested.
     *
     * @param Request $request
     * @param string  $courseSlug
     *
     * @return Response
     */
    public function __invoke(Request $request, string $courseSlug): Response
    {
        $course = $this->contentful->findCourse($courseSlug);
        if (!$course) {
            throw new NotFoundHttpException($this->translator->trans('errorMessage404Course'));
        }
        $lessons = $course->get('lessons');
        $nextLesson = $lessons[0] ?? null;

        // Manage state of viewed lessons
        $visitedLessons = $this->responseFactory->updateVisitedLessonCookie($request, $course->getId());

        $this->breadcrumb->add('homeLabel', 'landing_page')
            ->add('coursesLabel', 'courses')
            ->add($course->get('title'), 'course', ['courseSlug' => $course->get('slug')], false)
        ;

        return $this->responseFactory->createResponse('course.html.twig', [
            'course' => $course,
            'lesson' => null,
            'lessons' => $lessons,
            'nextLesson' => $nextLesson,
            'visitedLessons' => $visitedLessons,
        ]);
    }
}
