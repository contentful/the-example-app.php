<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

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
     * @param string $method
     * @param string $url
     * @param int    $statusCode
     */
    protected function visit(string $method, string $url, int $statusCode = 200)
    {
        $this->client = static::createClient();

        $this->crawler = $this->client->request($method, $url);
        $this->response = $this->client->getResponse();

        $this->assertEquals($statusCode, $this->response->getStatusCode());
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
        return $this->assertEquals($expected, $this->crawler->filter($selector)->attr($attr));
    }

    /**
     * @param string[] $breadcrumb
     */
    protected function assertBreadcrumb(array $breadcrumb)
    {
        $this->crawler->filter('.breadcrumb a')->each(function (Crawler $item, int $index) use ($breadcrumb) {
            $currentBreadcrumb = $breadcrumb[$index];

            $this->assertEquals($item->text(), $currentBreadcrumb[0]);
            $this->assertEquals($item->attr('href'), $currentBreadcrumb[1]);
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
}
