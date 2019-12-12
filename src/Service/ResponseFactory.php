<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2015-2019 Contentful GmbH
 * @license   MIT
 */

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

/**
 * ResponseFactory.
 *
 * This class is used in the app to simplify the handling of Response objects.
 * It also contain handy methods for dealing with cookies, whose values are
 * always encoded as JSON strings.
 */
class ResponseFactory
{
    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var int
     */
    private $cookieLifetime;

    /**
     * @var Cookie[]
     */
    private $cookies = [];

    /**
     * @var bool
     */
    private $clearSettingsCookie = false;

    public function __construct(Environment $twig, UrlGeneratorInterface $urlGenerator, int $cookieLifetime)
    {
        $this->twig = $twig;
        $this->urlGenerator = $urlGenerator;
        $this->cookieLifetime = $cookieLifetime;
    }

    /**
     * @param mixed $value
     */
    public function addCookie(string $name, $value): void
    {
        $this->cookies[] = new Cookie(
            $name,
            \json_encode($value),
            \time() + $this->cookieLifetime,
            '/',
            null,
            false,
            true,
            false,
            null
        );
    }

    /**
     * Removes the settings cookie.
     */
    public function clearSettingsCookie(): void
    {
        $this->clearSettingsCookie = true;
    }

    public function createResponse(string $template, array $parameters = [], int $statusCode = 200): Response
    {
        $body = $this->twig->render($template, $parameters);

        return $this->applyCookies(new Response($body, $statusCode));
    }

    public function createRedirectResponse(string $url): RedirectResponse
    {
        return $this->applyCookies(new RedirectResponse($url));
    }

    public function createRoutedRedirectResponse(string $route, array $parameters = []): RedirectResponse
    {
        $url = $this->urlGenerator->generate($route, $parameters);

        return $this->createRedirectResponse($url);
    }

    /**
     * Applies stored cookies to the current Response object.
     */
    private function applyCookies(Response $response): Response
    {
        foreach ($this->cookies as $cookie) {
            $response->headers->setCookie($cookie);
        }

        if ($this->clearSettingsCookie) {
            $response->headers->clearCookie(Contentful::COOKIE_SETTINGS_NAME);
        }

        return $response;
    }

    public function updateVisitedLessonCookie(Request $request, string $entryId): array
    {
        $cookie = $request->cookies->get('visitedLessons');
        $visitedLessons = $cookie ? \json_decode($cookie, true) : [];
        $visitedLessons[] = $entryId;

        $this->addCookie('visitedLessons', \array_unique($visitedLessons));

        return $visitedLessons;
    }
}
