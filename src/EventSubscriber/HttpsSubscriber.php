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
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * HttpsSubscriber.
 *
 * Here we force a redirect to HTTPS when in a prod environment.
 * This could be achieved by using the Security component:
 * https://symfony.com/doc/current/security/force_https.html
 * However, this app doesn't use such component anywhere else,
 * and to use it just for a redirect would probably be overkill.
 * Therefore, we use Symfony's event system to place a check as early
 * as possible by defining the high priority "4096" in getSubscribedEvents(),
 * as visible by using php bin/console debug:event-dispatcher.
 */
class HttpsSubscriber implements EventSubscriberInterface
{
    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * @param ResponseFactory $responseFactory
     */
    public function __construct(ResponseFactory $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event): void
    {
        $request = $event->getRequest();
        if ('https' === $request->getScheme() || '127.0.0.1' === $request->server->get('REMOTE_ADDR')) {
            return;
        }

        $uri = 'https://'.$request->getHttpHost().$request->getRequestUri();
        $event->setResponse($this->responseFactory->createRedirectResponse($uri));
    }

    /**
     * @return array[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 4096]],
        ];
    }
}
