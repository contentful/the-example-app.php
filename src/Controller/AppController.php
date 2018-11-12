<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2015-2018 Contentful GmbH
 * @license   MIT
 */

declare(strict_types=1);

namespace App\Controller;

use App\Service\Breadcrumb;
use App\Service\Contentful;
use App\Service\ResponseFactory;
use App\Service\State;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * AppController class.
 *
 * We provide a base class for handling dependencies
 * that are shared among most controllers.
 */
abstract class AppController
{
    /**
     * @var ResponseFactory
     */
    protected $responseFactory;

    /**
     * @var State
     */
    protected $state;

    /**
     * @var Breadcrumb
     */
    protected $breadcrumb;

    /**
     * @var Contentful
     */
    protected $contentful;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param ResponseFactory     $responseFactory
     * @param State               $state
     * @param Breadcrumb          $breadcrumb
     * @param Contentful          $contentful
     * @param TranslatorInterface $translator
     */
    public function __construct(
        ResponseFactory $responseFactory,
        State $state,
        Breadcrumb $breadcrumb,
        Contentful $contentful,
        TranslatorInterface $translator
    ) {
        $this->responseFactory = $responseFactory;
        $this->state = $state;
        $this->breadcrumb = $breadcrumb;
        $this->contentful = $contentful;
        $this->translator = $translator;
    }
}
