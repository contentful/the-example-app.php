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
use App\Service\Contentful;
use App\Service\ResponseFactory;
use App\Service\State;

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
     * @param ResponseFactory $responseFactory
     * @param State           $state
     * @param Breadcrumb      $breadcrumb
     * @param Contentful      $contentful
     */
    public function __construct(
        ResponseFactory $responseFactory,
        State $state,
        Breadcrumb $breadcrumb,
        Contentful $contentful
    ) {
        $this->responseFactory = $responseFactory;
        $this->state = $state;
        $this->breadcrumb = $breadcrumb;
        $this->contentful = $contentful;
    }
}
