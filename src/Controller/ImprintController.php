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
use App\Service\ResponseFactory;
use Symfony\Component\HttpFoundation\Response;

/**
 * ImprintController.
 */
class ImprintController
{
    /**
     * Renders imprint page when `/imprint` route is requested.
     *
     * @param ResponseFactory $responseFactory
     * @param Breadcrumb      $breadcrumb
     *
     * @return Response
     */
    public function __invoke(ResponseFactory $responseFactory, Breadcrumb $breadcrumb): Response
    {
        $breadcrumb->add('homeLabel', 'landing_page')
            ->add('imprintLabel', 'imprint');

        return $responseFactory->createResponse('imprint.html.twig');
    }
}
