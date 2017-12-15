<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2017 Contentful GmbH
 * @license   MIT
 */
declare(strict_types=1);

namespace App\Controller;

use App\Service\Breadcrumb;
use App\Service\Contentful;
use App\Service\ResponseFactory;
use Contentful\Delivery\DynamicEntry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * CourseController class.
 */
class CourseController
{
    /**
     * Renders a course when either `/courses/{courseSlug}` or `/courses/{courseSlug}/lesson/{lessonSlug}` is requested.
     *
     * @param Request         $request
     * @param ResponseFactory $responseFactory
     * @param Breadcrumb      $breadcrumb
     * @param Contentful      $contentful
     * @param string          $courseSlug
     * @param string|null     $lessonSlug
     *
     * @return Response
     */
    public function __invoke(
        Request $request,
        ResponseFactory $responseFactory,
        Breadcrumb $breadcrumb,
        Contentful $contentful,
        string $courseSlug,
        ?string $lessonSlug
    ): Response {
        $course = $contentful->findCourse($courseSlug, null !== $lessonSlug);
        if (!$course) {
            throw new NotFoundHttpException();
        }
        $lessons = $course->getLessons();

        ['lesson' => $lesson, 'nextLesson' => $nextLesson] = $this->findLesson($lessons, $lessonSlug);

        if ($lessonSlug and !$lesson) {
            throw new NotFoundHttpException();
        }

        // Manage state of viewed lessons
        $visitedLessons = $this->updateVisitedLessonCookie($request, $responseFactory, $lessonSlug ? $lesson->getId() : $course->getId());

        $this->setBreadcrumb($breadcrumb, $course, $lesson);

        return $responseFactory->createResponse('course.html.twig', [
            'course' => $course,
            'lesson' => $lesson,
            'lessons' => $lessons,
            'nextLesson' => $nextLesson,
            'visitedLessons' => $visitedLessons,
        ]);
    }

    /**
     * @param Breadcrumb        $breadcrumb
     * @param DynamicEntry      $course
     * @param DynamicEntry|null $lesson
     */
    private function setBreadcrumb(Breadcrumb $breadcrumb, DynamicEntry $course, ?DynamicEntry $lesson): void
    {
        $breadcrumb->add('homeLabel', 'landing_page')
            ->add('coursesLabel', 'courses')
            ->add($course->getTitle(), 'course', ['courseSlug' => $course->getSlug()], false);

        if ($lesson) {
            $breadcrumb->add('lessonsLabel', 'course', ['courseSlug' => $course->getSlug()])
                ->add($lesson->getTitle(), 'lesson', [
                    'courseSlug' => $course->getSlug(),
                    'lessonSlug' => $lesson->getSlug(),
                ], false);
        }
    }

    /**
     * @param array       $lessons
     * @param string|null $slug
     *
     * @return array
     */
    private function findLesson(array $lessons, ?string $slug): array
    {
        $lessonIndex = $this->findLessonIndex($lessons, $slug);

        return [
            'lesson' => $lessons[$lessonIndex] ?? null,
            'nextLesson' => $lessons[$lessonIndex + 1] ?? null,
        ];
    }

    /**
     * @param array       $lessons
     * @param string|null $slug
     *
     * @return int|null
     */
    private function findLessonIndex(array $lessons, ?string $slug): ?int
    {
        foreach ($lessons as $index => $lesson) {
            if ($lesson->getSlug() === $slug) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param Request         $request
     * @param ResponseFactory $responseFactory
     * @param string          $id
     *
     * @return array
     */
    private function updateVisitedLessonCookie(Request $request, ResponseFactory $responseFactory, string $id): array
    {
        $cookie = $request->cookies->get('visitedLessons');
        $visitedLessons = $cookie ? \json_decode($cookie, true) : [];
        $visitedLessons[] = $id;

        $responseFactory->addCookie('visitedLessons', \array_unique($visitedLessons));

        return $visitedLessons;
    }
}
