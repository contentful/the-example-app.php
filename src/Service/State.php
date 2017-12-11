<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2017 Contentful GmbH
 * @license   MIT
 */
declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;

/**
 * State.
 *
 * This class is used to store information about the current state of the app.
 * Once it is initialized, it can be available everywhere through the DI container,
 * and in templates through the "state" variable.
 */
class State
{
    /**
     * @var string
     */
    private $spaceId;

    /**
     * @var string
     */
    private $deliveryToken;

    /**
     * @var string
     */
    private $previewToken;

    /**
     * @var bool
     */
    private $editorialFeatures;

    /**
     * @var string
     */
    private $api;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var string[]
     */
    private $availableLocales;

    /**
     * @var string
     */
    private $queryString;

    /**
     * @var bool
     */
    private $cookieCredentials;

    /**
     * @var bool
     */
    private $usesDefaultCredentials = true;

    /**
     * @param Request|null $request
     * @param string       $spaceId
     * @param string       $deliveryToken
     * @param string       $previewToken
     * @param string       $locale
     * @param string[]     $availableLocales
     */
    public function __construct(?Request $request, string $spaceId, string $deliveryToken, string $previewToken, string $locale, array $availableLocales)
    {
        $settings = [
            'spaceId' => $spaceId,
            'deliveryToken' => $deliveryToken,
            'previewToken' => $previewToken,
            'locale' => $locale,
            'availableLocales' => $availableLocales,
            'editorialFeatures' => false,
            'api' => 'cda',
            'queryString' => '',
            'cookieCredentials' => false,
        ];

        // Request can be null when running the CLI.
        if ($request) {
            $settings = $this->extractValues($settings, $request);
        }

        foreach ($settings as $setting => $value) {
            $this->$setting = $value;
        }
    }

    /**
     * @param array   $settings
     * @param Request $request
     *
     * @return array
     */
    private function extractValues(array $settings, Request $request): array
    {
        $cookieSettings = (array) \json_decode(
            \stripslashes($request->cookies->get(Contentful::COOKIE_SETTINGS_NAME, '')),
            true
        );

        if ($this->hasCredentials($cookieSettings)) {
            $settings['cookieCredentials'] = true;
            $settings['spaceId'] = $cookieSettings['spaceId'];
            $settings['deliveryToken'] = $cookieSettings['deliveryToken'];
            $settings['previewToken'] = $cookieSettings['previewToken'];
            $settings['editorialFeatures'] = $cookieSettings['editorialFeatures'];

            $this->usesDefaultCredentials = false;
        }

        // The "enable_editorial_features" parameter
        // overrides the current settings.
        if ($request->query->has('enable_editorial_features')) {
            $settings['editorialFeatures'] = true;
        }

        $settings['api'] = $request->query->get('api', $settings['api']);
        $settings['locale'] = $request->query->get('locale', $settings['locale']);

        // http_build_query will automatically skip null values.
        $queryString = \http_build_query([
            'api' => $request->query->get('api'),
            'locale' => $request->query->get('locale'),
        ]);
        // We handle "enable_editorial_features" separately,
        // as it is a query parameter which has no value,
        // and http_build_query doesn't support this.
        if ($request->query->has('enable_editorial_features')) {
            $queryString .= ($queryString ? '&' : '').'enable_editorial_features';
        }
        if ($queryString) {
            $settings['queryString'] = '?'.$queryString;
        }

        return $settings;
    }

    /**
     * @param string[] $settings
     *
     * @return bool
     */
    private function hasCredentials(array $settings): bool
    {
        return isset($settings['spaceId'])
            && isset($settings['deliveryToken'])
            && isset($settings['previewToken'])
            && isset($settings['editorialFeatures']);
    }

    /**
     * Returns a representation of the current settings structure.
     *
     * @return string[]
     */
    public function getSettings(): array
    {
        return [
            'spaceId' => $this->spaceId,
            'deliveryToken' => $this->deliveryToken,
            'previewToken' => $this->previewToken,
            'editorialFeatures' => $this->editorialFeatures,
        ];
    }

    /**
     * @return string
     */
    public function getSpaceId(): string
    {
        return $this->spaceId;
    }

    /**
     * @return string
     */
    public function getDeliveryToken(): string
    {
        return $this->deliveryToken;
    }

    /**
     * @return string
     */
    public function getPreviewToken(): string
    {
        return $this->previewToken;
    }

    /**
     * @return bool
     */
    public function usesCookieCredentials(): bool
    {
        return $this->cookieCredentials;
    }

    /**
     * @return string
     */
    public function getApi(): string
    {
        return $this->api;
    }

    /**
     * @return string
     */
    public function getApiLabel(): string
    {
        return $this->isDeliveryApi()
            ? 'Content Delivery API'
            : 'Content Preview API';
    }

    /**
     * @return bool
     */
    public function isDeliveryApi(): bool
    {
        return $this->api == Contentful::API_DELIVERY;
    }

    /**
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * @return string[]
     */
    public function getAvailableLocales(): array
    {
        return $this->availableLocales;
    }

    /**
     * @return bool
     */
    public function hasEditorialFeaturesEnabled(): bool
    {
        return $this->editorialFeatures;
    }

    /**
     * @return string
     */
    public function getQueryString(): string
    {
        return $this->queryString;
    }

    /**
     * @return bool
     */
    public function hasEditorialFeaturesLink(): bool
    {
        return $this->editorialFeatures && $this->api == 'cpa';
    }

    /**
     * @return bool
     */
    public function usesDefaultCredentials(): bool
    {
        return $this->usesDefaultCredentials;
    }

    /**
     * @return string
     */
    public function getShareableLinkQuery(): string
    {
        return '?'.\http_build_query([
            'space_id' => $this->spaceId,
            'delivery_token' => $this->deliveryToken,
            'preview_token' => $this->previewToken,
            'api' => $this->api,
            'locale' => $this->locale,
        ]).($this->editorialFeatures ? '&enable_editorial_features' : '');
    }
}
