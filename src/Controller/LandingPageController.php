<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2017 Contentful GmbH
 * @license   MIT
 */
declare(strict_types=1);

namespace App\Controller;

use App\Service\Contentful;
use App\Service\ResponseFactory;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * LandingPageController.
 */
class LandingPageController
{
    /**
     * @param ResponseFactory $responseFactory
     * @param Contentful      $contentful
     * @param string          $landingPageSlug
     *
     * @return Response
     */
    public function __invoke(ResponseFactory $responseFactory, Contentful $contentful, string $landingPageSlug): Response
    {
        $landingPage = $contentful->findLandingPage($landingPageSlug);
        if (null === $landingPage) {
            throw new NotFoundHttpException();
        }

        return $responseFactory->createResponse('landingPage.html.twig', [
            'landingPage' => $landingPage,
        ]);
    }
}
