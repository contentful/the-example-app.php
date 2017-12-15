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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * CoursesController.
 */
class CoursesController
{
    /**
     * @param ResponseFactory $responseFactory
     * @param Breadcrumb      $breadcrumb
     * @param Contentful      $contentful
     * @param string|null     $categorySlug
     *
     * @return Response
     */
    public function __invoke(ResponseFactory $responseFactory, Breadcrumb $breadcrumb, Contentful $contentful, ?string $categorySlug): Response
    {
        $categories = $contentful->findCategories();
        $category = $this->findCategory($categories, $categorySlug);
        if ($categorySlug and !$category) {
            throw new NotFoundHttpException();
        }
        $courses = $contentful->findCourses($category);

        $breadcrumb->add('homeLabel', 'landing_page')
            ->add('coursesLabel', 'courses');

        if ($category) {
            $breadcrumb->add($category->getTitle(), 'category', ['categorySlug' => $categorySlug], false);
        }

        return $responseFactory->createResponse('courses.html.twig', [
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
