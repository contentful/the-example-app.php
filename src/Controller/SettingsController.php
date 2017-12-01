<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2017 Contentful GmbH
 * @license   MIT
 */
declare(strict_types=1);

namespace App\Controller;

use App\Form\SettingsType;
use App\Service\Breadcrumb;
use App\Service\Contentful;
use App\Service\ResponseFactory;
use App\Service\State;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * SettingsController.
 */
class SettingsController
{
    /**
     * @param Request              $request
     * @param ResponseFactory      $responseFactory
     * @param Breadcrumb           $breadcrumb
     * @param State                $state
     * @param Contentful           $contentful
     * @param FormFactoryInterface $formFactory
     *
     * @return Response
     */
    public function __invoke(
        Request $request,
        ResponseFactory $responseFactory,
        Breadcrumb $breadcrumb,
        State $state,
        Contentful $contentful,
        FormFactoryInterface $formFactory
    ): Response {
        $form = $formFactory->create(SettingsType::class, $state->getSettings());

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->updateSettings($responseFactory, $request->getSession(), $form->getData());

            return $responseFactory->createRoutedRedirectResponse('settings');
        }

        $breadcrumb->add('homeLabel', 'landing_page')
            ->add('settingsLabel', 'settings');

        return $responseFactory->createResponse('settings.html.twig', [
            'form' => $form->createView(),
            'space' => $contentful->findSpace(),
        ]);
    }

    private function updateSettings(ResponseFactory $responseFactory, SessionInterface $session, array $settings): void
    {
        $responseFactory->addCookie(
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
