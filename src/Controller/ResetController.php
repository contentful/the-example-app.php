<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2015-2018 Contentful GmbH
 * @license   MIT
 */

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * ResetController.
 */
class ResetController extends AppController
{
    /**
     * Clears the cookie where the settings are stored.
     *
     * @return RedirectResponse
     */
    public function __invoke(): RedirectResponse
    {
        $this->responseFactory->clearSettingsCookie();

        return $this->responseFactory->createRoutedRedirectResponse('settings');
    }
}
