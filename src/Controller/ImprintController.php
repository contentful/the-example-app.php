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

/**
 * ImprintController.
 */
class ImprintController extends AppController
{
    /**
     * Renders imprint page when `/imprint` route is requested.
     *
     * @return Response
     */
    public function __invoke(): Response
    {
        $this->breadcrumb->add('homeLabel', 'landing_page')
            ->add('imprintLabel', 'imprint')
        ;

        return $this->responseFactory->createResponse('imprint.html.twig');
    }
}
