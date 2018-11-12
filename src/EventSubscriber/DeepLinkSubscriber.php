<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2015-2018 Contentful GmbH
 * @license   MIT
 */

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Service\ResponseFactory;
use App\Service\State;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @param ResponseFactory       $responseFactory
     * @param UrlGeneratorInterface $urlGenerator
     */
    public function __construct(ResponseFactory $responseFactory, UrlGeneratorInterface $urlGenerator)
    {
        $this->responseFactory = $responseFactory;
        $this->urlGenerator = $urlGenerator;
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

        $event->setResponse($this->responseFactory->createRedirectResponse(
            $this->urlGenerator->generate('settings')
        ));
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
        ];

        if ($this->hasCredentials($request)) {
            $settings['spaceId'] = $queryParameters['space_id'];
            $settings['deliveryToken'] = $queryParameters['delivery_token'];
            $settings['previewToken'] = $queryParameters['preview_token'];
        }

        if ($this->hasEditorialFeatures($request)) {
            $settings['editorialFeatures'] = 'enabled' === $queryParameters['editorial_features'];
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
