<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2015-2018 Contentful GmbH
 * @license   MIT
 */

declare(strict_types=1);

namespace App\Controller;

use App\Form\Type\SettingsType;
use App\Service\Contentful;
use App\Service\State;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SettingsController.
 */
class SettingsController extends AppController
{
    /**
     * @param Request              $request
     * @param FormFactoryInterface $formFactory
     *
     * @return Response
     */
    public function __invoke(Request $request, FormFactoryInterface $formFactory): Response
    {
        $form = $formFactory->create(SettingsType::class, $this->state->getSettings());

        $redirectUrl = $this->handleSubmit($form, $request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->updateSettingsCookie($form->getData());

            if ($redirectUrl) {
                return $this->responseFactory->createRedirectResponse($redirectUrl);
            }

            // Let's add the flash success message only
            // if we're not redirecting the user.
            $request->getSession()
                ->getFlashBag()
                ->add('success', 'changesSavedLabel')
            ;

            return $this->responseFactory->createRoutedRedirectResponse('settings');
        }

        $this->breadcrumb->add('homeLabel', 'landing_page')
            ->add('settingsLabel', 'settings')
        ;

        return $this->responseFactory->createResponse('settings.html.twig', [
            'form' => $form->createView(),
            'space' => $this->contentful->findSpace(),
        ]);
    }

    /**
     * Submits the form taking care to use either actual form data,
     * or data stored in session by the DeepLinkSubscriber.
     *
     * @param FormInterface $form
     * @param Request       $request
     *
     * @return string|null
     */
    private function handleSubmit(FormInterface $form, Request $request): ?string
    {
        $settings = $request->getSession()->get(State::SESSION_SETTINGS_NAME);
        if (!$settings) {
            $form->handleRequest($request);

            return null;
        }

        $request->getSession()->remove(State::SESSION_SETTINGS_NAME);

        // Assign default values in case of partial settings being passed,
        // for instance when only setting credentials or the editorial features flag.
        $settings = \array_merge($this->state->getSettings(), $settings);

        $url = $settings['redirect'];
        unset($settings['redirect']);
        $form->submit($settings);

        return $url;
    }

    /**
     * @param string[] $settings
     */
    private function updateSettingsCookie(array $settings): void
    {
        $this->responseFactory->addCookie(
            Contentful::COOKIE_SETTINGS_NAME,
            [
                'spaceId' => $settings['spaceId'],
                'deliveryToken' => $settings['deliveryToken'],
                'previewToken' => $settings['previewToken'],
                'editorialFeatures' => (bool) ($settings['editorialFeatures'] ?? false),
            ]
        );
    }
}
