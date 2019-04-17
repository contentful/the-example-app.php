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
            ->add('coursesLabel', 'courses')
        ;

        if ($category) {
            $this->breadcrumb->add($category->get('title'), 'category', ['categorySlug' => $categorySlug], false);
        }

        return $this->responseFactory->createResponse('courses.html.twig', [
            'courses' => $courses,
            'categories' => $categories,
            'currentCategory' => $category,
        ]);
    }

    /**
     * @param Entry[]     $categories
     * @param string|null $categorySlug
     *
     * @return Entry|null
     */
    private function findCategory(array $categories, ?string $categorySlug): ?Entry
    {
        foreach ($categories as $category) {
            if ($category->get('slug') === $categorySlug) {
                return $category;
            }
        }

        return null;
    }
}
