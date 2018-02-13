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
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * DeepLinkSubscriber.
 *
 * This subscriber is used in order to intercept calls made to the app
 * when setting space_id, delivery_token and preview_token as query parameters.
 * When doing so, we intercept the call, and redirect to the settings page,
 * where validation is performed. If credentials are valid, the user will be
 * sent to the original URL.
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
        if ($request->isMethod('POST') || !$this->hasParameters($request)) {
            return;
        }

        $settings = $this->extractSettingsParameters($request);
        $request->getSession()->set(State::SESSION_SETTINGS_NAME, $settings);

        $event->setResponse($this->responseFactory->createRoutedRedirectResponse('settings'));
    }

    /**
     * @param Request $request
     *
     * @return string[]
     */
    private function extractSettingsParameters(Request $request): array
    {
        $queryParameters = $request->query->all();
        $redirect = $request->getPathInfo();

        $extraParameters = \http_build_query([
            'api' => $request->query->get('api'),
            'locale' => $request->query->get('locale'),
        ]);
        if ($extraParameters) {
            $redirect .= '?'.$extraParameters;
        }

        $settings = [
            'redirect' => $redirect,
            'spaceId' => $queryParameters['space_id'] ?? null,
            'deliveryToken' => $queryParameters['delivery_token'] ?? null,
            'previewToken' => $queryParameters['preview_token'] ?? null,
        ];

        if ('enabled' === ($queryParameters['editorial_features'] ?? 'disabled')) {
            $settings['editorialFeatures'] = true;
        }

        return $settings;
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
     * @return array[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 18]],
        ];
    }
}
