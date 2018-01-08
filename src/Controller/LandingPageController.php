<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2017 Contentful GmbH
 * @license   MIT
 */
declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * LandingPageController.
 */
class LandingPageController extends AppController
{
    /**
     * @param string $landingPageSlug
     *
     * @return Response
     */
    public function __invoke(string $landingPageSlug): Response
    {
        $landingPage = $this->contentful->findLandingPage($landingPageSlug);
        if (null === $landingPage) {
            throw new NotFoundHttpException();
        }

        return $this->responseFactory->createResponse('landingPage.html.twig', [
            'landingPage' => $landingPage,
        ]);
    }
}
