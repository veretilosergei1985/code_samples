<?php

namespace CardPrinterService\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ElementsConstraintValidator extends ConstraintValidator
{
    public const FIELDS = [
        'TEXT' => [
            'x' => 'integer',
            'y' => 'integer',
            'textContent' => 'string',
            'fieldType' => ['CUSTOM', 'FIRSTNAME', 'LASTNAME', 'FULLNAME', 'FILE_NUMBER', 'CARD_NUMBER'],
            'font' => 'string',
            'style' => ['NORMAL', 'BOLD', 'ITALIC'],
            'size' => 'integer',
            'color' => 'custom_hex',
        ],
        'IMAGE' => [
            'x' => 'integer',
            'y' => 'integer',
            'color' => 'custom_hex',
            'sourceType' => ['CUSTOM', 'PASSENGER_PICTURE'],
            'assetId' => 'string',
            'isPrintable' => 'boolean',
            'height' => 'integer',
            'width' => 'integer',
        ],
        'QRCODE' => [
            'x' => 'integer',
            'y' => 'integer',
            'urlType' => ['CUSTOM', 'PASSENGER_TOKEN'],
            'content' => 'string',
            'backgroundColor' => 'custom_hex',
            'foregroundColor' => 'custom_hex',
            'redundancyLevel' => ['L', 'M', 'Q', 'H'],
            'margin' => 'integer',
            'dimensions' => 'integer',
        ],
    ];

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ElementsConstraint) {
            throw new UnexpectedTypeException($constraint, ElementsConstraint::class);
        }

        if (!is_array($value)) {
            $this->addViolationToContext($constraint->message);

            return;
        }

        $elements = $value;

        /** @var array $element */
        foreach ($elements as $element) {
            $element = $this->prepareElement($element);
            if (!is_array($element) || !array_key_exists('type', $element) || !in_array($element['type'], array_keys(self::FIELDS))) {
                $this->addViolationToContext("Element is either not array, doesn't contain \"type\" property or the value of the \"type\" property is not valid.");

                return;
            }

            $this->validateElementByType($element);
        }
    }

    private function addViolationToContext(string $message): void
    {
        $this->context->buildViolation($message)
            ->addViolation();
    }

    private function validateElementByType(array $element): void
    {
        $type = $element['type'];
        $fieldsConfig = self::FIELDS[$type];

        $missingFields = array_diff_key($fieldsConfig, $element);
        if (count($missingFields) > 0) {
            $this->addViolationToContext("Element doesn't contain required fields: ".implode(',', array_keys($missingFields)).'.');
        }

        foreach ($element as $fieldName => $fieldValue) {
            if ($fieldName === 'type') {
                continue;
            }
            $fieldType = $fieldsConfig[$fieldName];
            if (is_array($fieldType)) {
                if (!in_array($fieldValue, $fieldType)) {
                    $this->addViolationToContext('Value: '.$fieldValue." doesn't have the right value (".implode(',', $fieldType).').');
                }
            } elseif ($fieldType === 'custom_hex') {
                if (!preg_match('/^#[a-f0-9]{6}$/i', $fieldValue)) {
                    $this->addViolationToContext('Value: '.$fieldValue." doesn't have right hex color value.");
                }
            } else {
                if (gettype($fieldValue) !== $fieldType) {
                    $this->addViolationToContext('Value: '.$fieldValue." doesn't have the right value (expected ".$fieldType.').');
                }
            }
        }
    }

    /**
     * This method is used to transform the element array to ['type' => '...', 'x' => ..., 'y' => ...]
     * instead of [['type' => '...'], ['x' => ...], ['y' => ...]] (this is the structure for PATCH, build by ApiPlatform).
     */
    private function prepareElement(array $element): array
    {
        if (!isset($element[0])) {
            return $element;
        }

        $newElement = [];

        foreach ($element as $elementItem) {
            $newElement = array_merge($newElement, $elementItem);
        }

        return $newElement;
    }
}
