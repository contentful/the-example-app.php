<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2017 Contentful GmbH
 * @license   MIT
 */
declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Response;

/**
 * ExceptionController class.
 */
class ExceptionController extends AppController
{
    /**
     * @param FlattenException $exception
     *
     * @return Response
     */
    public function __invoke(FlattenException $exception): Response
    {
        return $this->responseFactory->createResponse('error.html.twig', [
            'exception' => $exception,
        ], $exception->getStatusCode());
    }
}
