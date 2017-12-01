<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2017 Contentful GmbH
 * @license   MIT
 */
declare(strict_types=1);

namespace App\EventSubscriber;

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
     * @param State $state
     */
    public function __construct(State $state)
    {
        $this->state = $state;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event): void
    {
        $request = $event->getRequest();

        $request->setLocale($this->state->getLocale());
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
