<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2015-2018 Contentful GmbH
 * @license   MIT
 */

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AppWebTestCase extends WebTestCase
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var Crawler
     */
    protected $crawler;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var Request
     */
    protected $request;

    public function setUp()
    {
        $this->client = static::createClient();
    }

    /**
     * @param string $method
     * @param string $url
     * @param int    $statusCode
     */
    protected function visit(string $method, string $url, int $statusCode = 200)
    {
        $this->crawler = $this->client->request($method, $url);
        $this->request = $this->client->getRequest();
        $this->response = $this->client->getResponse();

        $this->assertSame($statusCode, $this->response->getStatusCode());
    }

    /**
     * @param string      $selector
     * @param string|null $value
     */
    protected function assertPageContains(string $selector, string $value = null)
    {
        $selector .= $value ? ':contains("'.$value.'")' : '';

        return $this->assertGreaterThan(0, $this->crawler->filter($selector)->count());
    }

    /**
     * @param string $selector
     * @param string $attr
     * @param string $expected
     */
    protected function assertPageContainsAttr(string $selector, string $attr, string $expected)
    {
        return $this->assertSame($expected, $this->crawler->filter($selector)->attr($attr));
    }

    /**
     * @param string[] $breadcrumb
     */
    protected function assertBreadcrumb(array $breadcrumb)
    {
        $this->crawler->filter('.breadcrumb a')->each(function (Crawler $item, int $index) use ($breadcrumb) {
            $currentBreadcrumb = $breadcrumb[$index];

            $this->assertSame($item->text(), $currentBreadcrumb[0]);
            $this->assertSame($item->attr('href'), $currentBreadcrumb[1]);
        });
    }

    /**
     * @param int  $min
     * @param int  $value
     * @param int  $max
     * @param bool $includeBoundaries
     */
    protected function assertBetween(int $min, int $value, int $max, bool $includeBoundaries = true)
    {
        $constraint = $includeBoundaries
            ? $this->logicalAnd(
                $this->greaterThanOrEqual($min),
                $this->lessThanOrEqual($max)
            )
            : $this->logicalAnd(
                $this->greaterThan($min),
                $this->lessThan($max)
            );

        $this->assertThat(
            $value,
            $constraint
        );
    }

    /**
     * Shortcut function for children classes.
     */
    protected function followRedirect(): void
    {
        $this->crawler = $this->client->followRedirect();
        $this->request = $this->client->getRequest();
        $this->response = $this->client->getResponse();
    }
}
