<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2015-2018 Contentful GmbH
 * @license   MIT
 */

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\ResponseFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Twig\Environment;

class ResponseFactoryTest extends TestCase
{
    /**
     * @var string
     */
    private $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Dummy template</title></head><body><h1>Hello, world!</h1></body></html>';

    /**
     * @var Environment
     */
    private $twig;

    public function setUp()
    {
        $this->twig = $this->createMock(Environment::class);
        $this->twig->method('render')
            ->willReturn($this->html)
        ;
    }

    public function testStandardResponse()
    {
        $responseFactory = new ResponseFactory($this->twig, new ResponseFactoryTestUrlGenerator(), 3600);
        $responseFactory->addCookie('TestCookie', '1337');

        $response = $responseFactory->createResponse('fakePath.html.twig');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($this->html, $response->getContent());

        $cookies = $response->headers->getCookies();
        $this->assertCount(1, $cookies);
        $cookie = $cookies[0];
        $this->assertSame('TestCookie', $cookie->getName());
        $this->assertSame('"1337"', $cookie->getValue());
        $this->assertLessThanOrEqual(\time() + 3600, $cookie->getExpiresTime());
    }

    public function testRedirectResponse()
    {
        $responseFactory = new ResponseFactory($this->twig, new ResponseFactoryTestUrlGenerator(), 3600);
        $response = $responseFactory->createRedirectResponse('https://www.example.com');

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('https://www.example.com', $response->getTargetUrl());
    }

    public function testRoutedRedirectResponse()
    {
        $responseFactory = new ResponseFactory($this->twig, new ResponseFactoryTestUrlGenerator(), 3600);
        $response = $responseFactory->createRoutedRedirectResponse('route1', ['param1' => 'value1']);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/route1-{"param1":"value1"}', $response->getTargetUrl());
    }
}

class ResponseFactoryTestUrlGenerator implements UrlGeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH)
    {
        return '/'.$name.'-'.\json_encode($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(RequestContext $context)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getContext()
    {
    }
}
