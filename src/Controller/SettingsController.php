<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2017 Contentful GmbH
 * @license   MIT
 */
declare(strict_types=1);

namespace App\Controller;

use App\Form\Type\SettingsType;
use App\Service\Contentful;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

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

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->updateSettings($request->getSession(), $form->getData());

            return $this->responseFactory->createRoutedRedirectResponse('settings');
        }

        $this->breadcrumb->add('homeLabel', 'landing_page')
            ->add('settingsLabel', 'settings');

        return $this->responseFactory->createResponse('settings.html.twig', [
            'form' => $form->createView(),
            'space' => $this->contentful->findSpace(),
        ]);
    }

    private function updateSettings(SessionInterface $session, array $settings): void
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

        $session->getFlashBag()
            ->add('success', 'changesSavedLabel');
    }
}
