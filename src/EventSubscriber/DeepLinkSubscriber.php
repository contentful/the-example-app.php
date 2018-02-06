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
use App\Service\State;
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
     * @var State
     */
    private $state;

    /**
     * @param ResponseFactory $responseFactory
     * @param Contentful      $contentful
     * @param State           $state
     */
    public function __construct(ResponseFactory $responseFactory, Contentful $contentful, State $state)
    {
        $this->responseFactory = $responseFactory;
        $this->contentful = $contentful;
        $this->state = $state;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event): void
    {
        $request = $event->getRequest();
        if ($request->isMethod('POST') || !$this->hasParameters($request)) {
            return;
        }

        if (!$this->validateParameters($request)) {
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
     * @param Request $request
     *
     * @return bool
     */
    private function hasParameters(Request $request): bool
    {
        return $this->hasCredentials($request) || $this->hasEditorialFeatures($request);
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    private function hasCredentials(Request $request): bool
    {
        return $request->query->has('space_id')
            && $request->query->has('preview_token')
            && $request->query->has('delivery_token');
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    private function hasEditorialFeatures(Request $request): bool
    {
        return $request->query->has('editorial_features');
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
        $currentSettings = $this->state->getSettings();
        $queryParameters = $request->query->all();

        $this->responseFactory->addCookie(
            Contentful::COOKIE_SETTINGS_NAME,
            [
                'spaceId' => $queryParameters['space_id'] ?? $currentSettings['spaceId'],
                'deliveryToken' => $queryParameters['delivery_token'] ?? $currentSettings['deliveryToken'],
                'previewToken' => $queryParameters['preview_token'] ?? $currentSettings['previewToken'],
                'editorialFeatures' => isset($queryParameters['editorial_features'])
                    ? 'enabled' === $queryParameters['editorial_features']
                    : $currentSettings['editorialFeatures'],
            ]
        );

        unset(
            $queryParameters['space_id'],
            $queryParameters['delivery_token'],
            $queryParameters['preview_token'],
            $queryParameters['editorial_features']
        );

        return $queryParameters;
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    private function validateParameters(Request $request): bool
    {
        try {
            if ($this->hasCredentials($request)) {
                $this->validateCredentials($request);
            }

            if ($this->hasEditorialFeatures($request)) {
                $this->validateEditorialFeatures($request);
            }

            return true;
        } catch (\Exception $exception) {
            $exception = FlattenException::create(
                $exception,
                $exception instanceof ApiException
                    ? $exception->getResponse()->getStatusCode()
                    : $exception->getCode()
            );

            $request->attributes->set('_controller', 'App\Controller\ExceptionController');
            $request->attributes->set('exception', $exception);

            return false;
        }
    }

    /**
     * Tries to call the API using the given credentials.
     *
     * @param Request $request
     */
    private function validateCredentials(Request $request): void
    {
        $queryParameters = $request->query->all();

        $this->contentful->validateCredentials(
            $queryParameters['space_id'],
            $queryParameters['delivery_token'],
            Contentful::API_DELIVERY
        );
        $this->contentful->validateCredentials(
            $queryParameters['space_id'],
            $queryParameters['preview_token'],
            Contentful::API_PREVIEW
        );
    }

    /**
     * @param Request $request
     *
     * @throws \InvalidArgumentException
     */
    private function validateEditorialFeatures(Request $request): void
    {
        $editorialFeatures = $request->query->get('editorial_features');

        if (!in_array($editorialFeatures, [null, 'enabled', 'disabled'], true)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid value for editorial_features parameter: %s',
                $editorialFeatures
            ), 400);
        }
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
