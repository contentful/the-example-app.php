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
     * @var string
     */
    private $queryString;

    /**
     * @var bool
     */
    private $cookieCredentials;

    /**
     * @param Request|null $request
     * @param string       $spaceId
     * @param string       $deliveryToken
     * @param string       $previewToken
     * @param string       $locale
     */
    public function __construct(?Request $request, string $spaceId, string $deliveryToken, string $previewToken, string $locale)
    {
        $settings = [
            'spaceId' => $spaceId,
            'deliveryToken' => $deliveryToken,
            'previewToken' => $previewToken,
            'locale' => $locale,
            'editorialFeatures' => false,
            'api' => 'cda',
            'queryString' => '',
            'cookieCredentials' => false,
        ];

        // Request can be null when running the CLI.
        if ($request) {
            $settings = \array_merge($settings, $this->extractValues($request));
        }

        foreach ($settings as $setting => $value) {
            $this->$setting = $value;
        }
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    private function extractValues(Request $request): array
    {
        $settings = $this->extractCookieSettings($request);

        // The "enable_editorial_features" parameter overrides the current settings.
        $settings['editorialFeatures'] = ($settings['editorialFeatures'] ?? false) || $request->query->has('enable_editorial_features');

        $settings['api'] = $request->query->get('api');
        $settings['locale'] = $request->query->get('locale');

        $settings['queryString'] = $this->extractQueryString($request);

        return \array_filter($settings);
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    private function extractCookieSettings(Request $request): array
    {
        $cookieSettings = (array) \json_decode(
            \stripslashes($request->cookies->get(Contentful::COOKIE_SETTINGS_NAME, '')),
            true
        );

        $settings = [];

        if ($cookieSettings) {
            $settings['cookieCredentials'] = true;
            $settings['spaceId'] = $cookieSettings['spaceId'];
            $settings['deliveryToken'] = $cookieSettings['deliveryToken'];
            $settings['previewToken'] = $cookieSettings['previewToken'];
            $settings['editorialFeatures'] = $cookieSettings['editorialFeatures'];
        }

        return $settings;
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    private function extractQueryString(Request $request): string
    {
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

        return $queryString ? '?'.$queryString : '';
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
        return Contentful::API_DELIVERY === $this->api;
    }

    /**
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale;
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
        return $this->editorialFeatures && 'cpa' === $this->api;
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
