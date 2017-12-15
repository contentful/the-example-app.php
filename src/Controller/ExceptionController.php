<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2017 Contentful GmbH
 * @license   MIT
 */
declare(strict_types=1);

namespace App\Controller;

use App\Service\ResponseFactory;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Response;

/**
 * ExceptionController class.
 */
class ExceptionController
{
    /**
     * @param ResponseFactory  $responseFactory
     * @param FlattenException $exception
     *
     * @return Response
     */
    public function __invoke(ResponseFactory $responseFactory, FlattenException $exception): Response
    {
        return $responseFactory->createResponse('error.html.twig', [
            'exception' => $exception,
        ], $exception->getStatusCode());
    }
}
