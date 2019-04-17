<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2015-2018 Contentful GmbH
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
     * @var string
     */
    const HOME_SLUG = 'home';

    /**
     * @return Response
     */
    public function __invoke(): Response
    {
        $landingPage = $this->contentful->findLandingPage(self::HOME_SLUG);
        if (null === $landingPage) {
            throw new NotFoundHttpException($this->translator->trans('errorMessage404Route'));
        }

        return $this->responseFactory->createResponse('landingPage.html.twig', [
            'landingPage' => $landingPage,
        ]);
    }
}
