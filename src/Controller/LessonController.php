<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2015-2018 Contentful GmbH
 * @license   MIT
 */

declare(strict_types=1);

namespace App\Controller;

use Contentful\Delivery\Resource\Entry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * LessonController class.
 */
class LessonController extends AppController
{
    /**
     * Renders a lesson when `/courses/{courseSlug}/lesson/{lessonSlug}` is requested.
     *
     * @param Request $request
     * @param string  $courseSlug
     * @param string  $lessonSlug
     *
     * @return Response
     */
    public function __invoke(Request $request, string $courseSlug, string $lessonSlug): Response
    {
        $course = $this->contentful->findCourseByLesson($courseSlug, $lessonSlug);

        // $course will be null even in the case of an existing course with a non-existing lesson.
        if (!$course) {
            throw new NotFoundHttpException($this->translator->trans('errorMessage404Lesson'));
        }

        // Manage state of viewed lessons
        $visitedLessons = $this->responseFactory->updateVisitedLessonCookie($request, $course->lesson->getId());

        $this->setBreadcrumb($course, $course->lesson);

        return $this->responseFactory->createResponse('course.html.twig', [
            'course' => $course,
            'lesson' => $course->lesson,
            'lessons' => $course->get('lessons'),
            'nextLesson' => $course->nextLesson,
            'visitedLessons' => $visitedLessons,
        ]);
    }

    /**
     * @param Entry $course
     * @param Entry $lesson
     */
    private function setBreadcrumb(Entry $course, Entry $lesson): void
    {
        $this->breadcrumb->add('homeLabel', 'landing_page')
            ->add('coursesLabel', 'courses')
            ->add($course->get('title'), 'course', ['courseSlug' => $course->get('slug')], false)
            ->add('lessonsLabel', 'course', ['courseSlug' => $course->get('slug')])
            ->add($lesson->get('title'), 'lesson', [
                'courseSlug' => $course->get('slug'),
                'lessonSlug' => $lesson->get('slug'),
            ], false)
        ;
    }
}
