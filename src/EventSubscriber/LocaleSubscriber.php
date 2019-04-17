<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2015-2018 Contentful GmbH
 * @license   MIT
 */

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Service\Contentful;
use App\Service\ResponseFactory;
use App\Service\State;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * LocaleSubscriber.
 *
 * Symfony determines the user locale from the request object.
 * This is normally a good approach, however we're implementing
 * custom logic in order to determine which locale the user has active.
 * Thanks to DI, we can get an object of the App\Service\State class here,
 * and we use that to set the request's locale accordingly.
 */
class LocaleSubscriber implements EventSubscriberInterface
{
    /**
     * @var State
     */
    private $state;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * @var Contentful
     */
    private $contentful;

    /**
     * @var string[]
     */
    private $availableLocales;

    /**
     * @param State           $state
     * @param ResponseFactory $responseFactory
     * @param Contentful      $contentful
     * @param string[]        $availableLocales
     */
    public function __construct(State $state, ResponseFactory $responseFactory, Contentful $contentful, array $availableLocales)
    {
        $this->state = $state;
        $this->responseFactory = $responseFactory;
        $this->contentful = $contentful;
        $this->availableLocales = $availableLocales;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event): void
    {
        $request = $event->getRequest();
        $locale = $this->state->getLocale();

        if (!$this->apiSupportsLocale($locale)) {
            // By the time we reach this subscriber, there are only
            // two meaningful query parameters available: api and locale.
            // By removing the invalid locale, we only need to care about adding
            // the api parameter to the query string, and only if it's not already
            // using the default value ("cda").
            $params = $request->attributes->get('_route_params');
            if (!$this->state->isDeliveryApi()) {
                $params['api'] = Contentful::API_PREVIEW;
            }

            $response = $this->responseFactory->createRoutedRedirectResponse(
                $request->attributes->get('_route'),
                $params
            );
            $event->setResponse($response);

            return;
        }

        $request->setLocale($locale);
    }

    /**
     * In order to check if a locale is supported,
     * we first simply check a list of statically defined locales
     * (those available in this app), and if that fails,
     * we query the "/spaces/xxx" endpoint, which contains the info we need.
     * The space result is actually cached, so if the query succeeds
     * and the locale is indeed supported in the Contentful space,
     * there is no performance penalty, as it is always retrieved from the SDK anyway.
     *
     * @param string $locale
     *
     * @return bool
     */
    private function apiSupportsLocale(string $locale): bool
    {
        if (\in_array($locale, $this->availableLocales, true)) {
            return true;
        }

        try {
            $this->contentful
                ->findEnvironment()
                ->getLocale($locale)
            ;

            return true;
        } catch (\InvalidArgumentException $exception) {
            return false;
        }
    }

    /**
     * @return array[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            // The default locale listener is set at 16, so we want
            // to be right after that to be able to override it.
            KernelEvents::REQUEST => [['onKernelRequest', 14]],
        ];
    }
}
