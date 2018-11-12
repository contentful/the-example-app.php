<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2015-2018 Contentful GmbH
 * @license   MIT
 */

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * NotFoundController class.
 */
class NotFoundController extends AppController
{
    /**
     * This works as a "catch-all" controller.
     * It is used in order to be able to specify
     * the error message in the exception object.
     */
    public function __invoke()
    {
        throw new NotFoundHttpException($this->translator->trans('errorMessage404Route'));
    }
}
