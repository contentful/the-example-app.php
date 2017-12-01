<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2017 Contentful GmbH
 * @license   MIT
 */
declare(strict_types=1);

namespace App\Validator\Constraints;

use App\Service\Contentful;
use Contentful\Exception\AccessTokenInvalidException;
use Contentful\Exception\ApiException;
use Contentful\Exception\NotFoundException;
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
     * @param Contentful $contentful
     */
    public function __construct(Contentful $contentful)
    {
        $this->contentful = $contentful;
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

        // Validate space ID and delivery token.
        // If any error arises, we return early and skip validating the preview token.
        try {
            $this->contentful->validateCredentials($values['spaceId'], $values['deliveryToken']);
        } catch (AccessTokenInvalidException $exception) {
            return $this->context->buildViolation('deliveryKeyInvalidLabel')
                ->atPath('[deliveryToken]')
                ->addViolation();
        } catch (NotFoundException $exception) {
            return $this->context->buildViolation('spaceOrTokenInvalid')
                ->atPath('[spaceId]')
                ->addViolation();
        } catch (ApiException $exception) {
            return $this->context->buildViolation('somethingWentWrongLabel')
                ->atPath('[deliveryToken]')
                ->addViolation();
        }

        // Validate space ID and preview token.
        try {
            $this->contentful->validateCredentials($values['spaceId'], $values['previewToken'], false);
        } catch (AccessTokenInvalidException $exception) {
            $this->context->buildViolation('deliveryKeyInvalidLabel')
                ->atPath('[previewToken]')
                ->addViolation();
        } catch (NotFoundException $exception) {
            $this->context->buildViolation('spaceOrTokenInvalid')
                ->atPath('[spaceId]')
                ->addViolation();
        } catch (ApiException $exception) {
            $this->context->buildViolation('somethingWentWrongLabel')
                ->atPath('[previewToken]')
                ->addViolation();
        }
    }
}
