<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2015-2020 Contentful GmbH
 * @license   MIT
 */

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Response;

/**
 * ErrorController class.
 */
class ErrorController extends AppController
{
    public function __invoke(FlattenException $exception): Response
    {
        return $this->responseFactory->createResponse('error.html.twig', [
            'exception' => $exception,
        ], $exception->getStatusCode());
    }
}
