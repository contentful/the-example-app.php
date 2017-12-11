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
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * ResetController.
 */
class ResetController
{
    /**
     * Clears the cookie where the settings are stored.
     *
     * @param ResponseFactory $responseFactory
     *
     * @return RedirectResponse
     */
    public function __invoke(ResponseFactory $responseFactory): RedirectResponse
    {
        $responseFactory->clearSettingsCookie();

        return $responseFactory->createRoutedRedirectResponse('settings');
    }
}
