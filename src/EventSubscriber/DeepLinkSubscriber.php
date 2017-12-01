<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2017 Contentful GmbH
 * @license   MIT
 */
declare(strict_types=1);

namespace App\EventSubscriber;

use App\Service\Contentful;
use App\Service\ResponseFactory;
use Contentful\Exception\ApiException;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * DeepLinkSubscriber.
 *
 * This subscriber is used in order to intercept calls made to the app
 * when setting spaceId, deliveryToken and previewToken as query parameters.
 * When doing so, we intercept the call, extract the values, set a cookie and
 * redirect the user to a "clean" version of the same URL.
 * As these three parameters are all require to form valid credentials,
 * if they're not all defined, we skip the process.
 */
class DeepLinkSubscriber implements EventSubscriberInterface
{
    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * @var Contentful
     */
    private $contentful;

    /**
     * @param ResponseFactory $responseFactory
     * @param Contentful      $contentful
     */
    public function __construct(ResponseFactory $responseFactory, Contentful $contentful)
    {
        $this->responseFactory = $responseFactory;
        $this->contentful = $contentful;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event): void
    {
        $request = $event->getRequest();
        if ($request->isMethod('POST') || !$request->query->has('space_id') || !$request->query->has('preview_token') || !$request->query->has('delivery_token')) {
            return;
        }

        if (!$this->validateCredentials($request)) {
            return;
        }

        $queryParameters = $this->cleanCookieParameters($request);

        $url = $request->getPathInfo();
        if ($queryParameters) {
            $url .= '?'.\http_build_query($queryParameters);
        }

        $event->setResponse($this->responseFactory->createRedirectResponse($url));
    }

    /**
     * This method extracts the query parameters from the request object,
     * sets a cookie, a returns a cleaned array ready for be used in a redirect.
     *
     * @param Request $request
     *
     * @return string[]
     */
    private function cleanCookieParameters(Request $request): array
    {
        $parameters = $request->query->all();
        $this->responseFactory->addCookie(
            Contentful::COOKIE_SETTINGS_NAME,
            [
                'spaceId' => $parameters['space_id'],
                'deliveryToken' => $parameters['delivery_token'],
                'previewToken' => $parameters['delivery_token'],
                'editorialFeatures' => false,
            ]
        );

        unset(
            $parameters['space_id'],
            $parameters['delivery_token'],
            $parameters['delivery_token']
        );

        return $parameters;
    }

    /**
     * Tries to call the API using the given credentials.
     * If the call fails, it will change the request object
     * and set the controller to the custom error one.
     *
     * @param Request $request
     *
     * @return bool
     */
    private function validateCredentials(Request $request): bool
    {
        $queryParameters = $request->query->all();

        try {
            $this->contentful->validateCredentials($queryParameters['spaceId'], $queryParameters['delivery_token']);
            $this->contentful->validateCredentials($queryParameters['spaceId'], $queryParameters['preview_token'], false);
        } catch (ApiException $exception) {
            $exception = FlattenException::create(
                $exception,
                $exception->getResponse()->getStatusCode()
            );

            $request->attributes->set('_controller', 'App\Controller\ExceptionController');
            $request->attributes->set('exception', $exception);

            return false;
        }

        return true;
    }

    /**
     * @return array[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 18]],
        ];
    }
}
