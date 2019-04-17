<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2015-2018 Contentful GmbH
 * @license   MIT
 */

declare(strict_types=1);

namespace App\Validator\Constraints;

use App\Service\Contentful;
use App\Service\State;
use Contentful\Core\Api\Exception;
use Contentful\Core\Exception\AccessTokenInvalidException;
use Contentful\Core\Exception\NotFoundException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * ContentfulCredentialsValidator.
 *
 * This validator applies to a whole form.
 * It will receive an array of data, and it will validate 3 fields:
 * - spaceId
 * - deliveryToken
 * - previewToken
 *
 * The only way of validating these values is to actually make an API call,
 * so we rely on the Contentful service to do that.
 */
class ContentfulCredentialsValidator extends ConstraintValidator
{
    /**
     * @var Contentful
     */
    private $contentful;

    /**
     * @var State
     */
    private $state;

    /**
     * @param Contentful $contentful
     * @param State      $state
     */
    public function __construct(Contentful $contentful, State $state)
    {
        $this->contentful = $contentful;
        $this->state = $state;
    }

    /**
     * @param string[]   $values
     * @param Constraint $constraint
     */
    public function validate($values, Constraint $constraint)
    {
        // Space ID, delivery token, and preview token are required.
        // This validation is performed at field level, therefore
        // we don't need to double-check for validity of credentials here.
        if (!isset($values['spaceId']) || !isset($values['deliveryToken']) || !isset($values['previewToken'])) {
            return;
        }

        if ($this->equalsStateCredentials($values)) {
            return;
        }

        $this->validateCredentials($values['spaceId'], $values['deliveryToken'], Contentful::API_DELIVERY);
        $this->validateCredentials($values['spaceId'], $values['previewToken'], Contentful::API_PREVIEW);
    }

    /**
     * Small optimization:
     * let's not check for validity of credentials when they equal
     * those currently in use, which are guaranteed to be correct.
     *
     * @param string[] $values
     *
     * @return bool
     */
    private function equalsStateCredentials(array $values): bool
    {
        return $this->state->getSpaceId() === $values['spaceId']
            && $this->state->getDeliveryToken() === $values['deliveryToken']
            && $this->state->getPreviewToken() === $values['previewToken'];
    }

    /**
     * @param string $spaceId
     * @param string $accessToken
     * @param string $api         Either "delivery" or "preview"
     */
    private function validateCredentials(string $spaceId, string $accessToken, string $api): void
    {
        $violation = null;
        $path = null;
        $apiLabel = Contentful::API_DELIVERY === $api ? 'delivery' : 'preview';

        try {
            $this->contentful->validateCredentials($spaceId, $accessToken, $api);
        } catch (AccessTokenInvalidException $exception) {
            $violation = $apiLabel.'KeyInvalidLabel';
            $path = '['.$apiLabel.'Token]';
        } catch (NotFoundException $exception) {
            $violation = 'spaceOrTokenInvalid';
            $path = '[spaceId]';
        } catch (Exception $exception) {
            $violation = 'somethingWentWrongLabel';
            $path = '['.$apiLabel.'Token]';
        }

        if ($violation) {
            $this->context->buildViolation($violation)
                ->atPath($path)
                ->addViolation()
            ;
        }
    }
}
