<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2017 Contentful GmbH
 * @license   MIT
 */
declare(strict_types=1);

namespace App\Controller;

use Contentful\Delivery\DynamicEntry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * CoursesController.
 */
class CoursesController extends AppController
{
    /**
     * @param string|null $categorySlug
     *
     * @return Response
     */
    public function __invoke(?string $categorySlug): Response
    {
        $categories = $this->contentful->findCategories();
        $category = $this->findCategory($categories, $categorySlug);
        if ($categorySlug && !$category) {
            throw new NotFoundHttpException($this->translator->trans('errorMessage404Category'));
        }
        $courses = $this->contentful->findCourses($category);

        $this->breadcrumb->add('homeLabel', 'landing_page')
            ->add('coursesLabel', 'courses');

        if ($category) {
            $this->breadcrumb->add($category->getTitle(), 'category', ['categorySlug' => $categorySlug], false);
        }

        return $this->responseFactory->createResponse('courses.html.twig', [
            'courses' => $courses,
            'categories' => $categories,
            'currentCategory' => $category,
        ]);
    }

    /**
     * @param DynamicEntry[] $categories
     * @param string|null    $categorySlug
     *
     * @return DynamicEntry|null
     */
    private function findCategory(array $categories, ?string $categorySlug): ?DynamicEntry
    {
        foreach ($categories as $category) {
            if ($category->getSlug() === $categorySlug) {
                return $category;
            }
        }

        return null;
    }
}
