<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2015-2018 Contentful GmbH
 * @license   MIT
 */

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\Breadcrumb;
use App\Service\State;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Translation\TranslatorInterface;

class BreadcrumbTest extends TestCase
{
    public function testBreadcrumbGeneration()
    {
        $breadcrumb = new Breadcrumb(
            new BreadcrumbTestTranslator(),
            new BreadcrumbTestUrlGenerator(),
            new State(null, [
                'space_id' => 'spaceId',
                'delivery_token' => 'deliveryToken',
                'preview_token' => 'previewToken',
            ], 'locale')
        );

        $breadcrumb->add('item1', 'route1', ['param1' => 'value1'])
            ->add('item2', 'route2', [], false)
        ;

        $this->assertSame([
            ['label' => 'item1-[]', 'url' => '/route1-{"param1":"value1"}'],
            ['label' => 'item2', 'url' => '/route2-[]'],
        ], $breadcrumb->getItems());
    }
}

class BreadcrumbTestTranslator implements TranslatorInterface
{
    /**
     * {@inheritdoc}
     */
    public function trans($id, array $parameters = [], $domain = null, $locale = null)
    {
        return $id.'-'.\json_encode($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function transChoice($id, $number, array $parameters = [], $domain = null, $locale = null)
    {
        return $id.'-'.$number.'-'.\json_encode($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function setLocale($locale)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getLocale()
    {
    }
}

class BreadcrumbTestUrlGenerator implements UrlGeneratorInterface
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
