<?php

namespace App\Core;

/**
 * Rule based server side validation.
 *
 * Usage:
 *
 *   $validator = new Validator($_POST);
 *   $validator->validate([
 *       'name'     => 'required|min:2|max:120',
 *       'email'    => 'required|email|max:190',
 *       'password' => 'required|min:8|confirmed',
 *   ]);
 *
 *   if ($validator->fails()) {
 *       // $validator->errors() is a field => [messages] map
 *   }
 *
 * Every form is validated here rather than relying on the browser, because
 * HTML validation can be skipped entirely by posting directly to the endpoint.
 */
class Validator
{
    /** @var array The input being validated */
    private $data;

    /** @var array field => [messages] */
    private $errors = [];

    /** @var array field => cleaned value */
    private $clean = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @param array $rules field => pipe separated rules
     */
    public function validate(array $rules)
    {
        $this->errors = [];
        $this->clean = [];

        foreach ($rules as $field => $ruleString) {
            $rulesForField = array_filter(explode('|', $ruleString));

            $value = array_key_exists($field, $this->data) ? $this->data[$field] : null;
            $label = $this->label($field);

            foreach ($rulesForField as $rule) {
                list($name, $parameter) = array_pad(explode(':', $rule, 2), 2, null);

                $this->applyRule($name, $parameter, $field, $label, $value);
            }

            // Optional fields keep an empty string rather than null so bound
            // parameters stay consistently typed.
            if (array_key_exists($field, $this->data)) {
                $this->clean[$field] = $this->normalise($value);
            }
        }

        return !$this->fails();
    }

    private function applyRule($name, $parameter, $field, $label, $value)
    {
        switch ($name) {
            case 'required':
                if ($value === null || (is_string($value) && trim($value) === '')) {
                    $this->addError($field, $label . ' is required.');
                }
                break;

            case 'email':
                if ($this->isPresent($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, 'Enter a valid email address.');
                }
                break;

            case 'min':
                if ($this->isPresent($value) && $this->length($value) < (int) $parameter) {
                    $this->addError($field, $label . ' must be at least ' . $parameter . ' characters.');
                }
                break;

            case 'max':
                if ($this->isPresent($value) && $this->length($value) > (int) $parameter) {
                    $this->addError($field, $label . ' may not be longer than ' . $parameter . ' characters.');
                }
                break;

            case 'numeric':
                if ($this->isPresent($value) && !is_numeric($value)) {
                    $this->addError($field, $label . ' must be a number.');
                }
                break;

            case 'integer':
                if ($this->isPresent($value) && filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $this->addError($field, $label . ' must be a whole number.');
                }
                break;

            case 'in':
                $allowed = explode(',', (string) $parameter);
                if ($this->isPresent($value) && !in_array((string) $value, $allowed, true)) {
                    $this->addError($field, $label . ' is not a valid choice.');
                }
                break;

            case 'date':
                if ($this->isPresent($value) && !$this->isValidDate($value)) {
                    $this->addError($field, 'Enter ' . lcfirst($label) . ' as a valid date (YYYY-MM-DD).');
                }
                break;

            case 'after_or_equal_today':
                if ($this->isPresent($value) && $this->isValidDate($value)) {
                    if (strtotime($value) < strtotime(date('Y-m-d'))) {
                        $this->addError($field, $label . ' cannot be in the past.');
                    }
                }
                break;

            case 'url':
                if ($this->isPresent($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->addError($field, 'Enter a valid URL, for example https://example.com');
                }
                break;

            case 'confirmed':
                $confirmation = array_key_exists($field . '_confirmation', $this->data)
                    ? $this->data[$field . '_confirmation']
                    : null;

                if ($this->isPresent($value) && $value !== $confirmation) {
                    $this->addError($field, $label . ' confirmation does not match.');
                }
                break;

            case 'same':
                $other = array_key_exists($parameter, $this->data) ? $this->data[$parameter] : null;

                if ($this->isPresent($value) && $value !== $other) {
                    $otherLabel = $this->label($parameter);
                    $this->addError($field, $label . ' must match ' . lcfirst($otherLabel) . '.');
                }
                break;

            case 'password_strength':
                if ($this->isPresent($value) && !$this->isStrongPassword($value)) {
                    $this->addError($field, $label . ' must be at least 8 characters and include a letter and a number.');
                }
                break;

            case 'nullable':
                // Marker rule handled by isPresent(); nothing to check.
                break;

            default:
                throw new \InvalidArgumentException('Unknown validation rule: ' . $name);
        }
    }

    private function addError($field, $message)
    {
        $this->errors[$field][] = $message;
    }

    private function isPresent($value)
    {
        return $value !== null && !(is_string($value) && trim($value) === '');
    }

    private function length($value)
    {
        return function_exists('mb_strlen') ? mb_strlen((string) $value) : strlen((string) $value);
    }

    private function isValidDate($value)
    {
        $date = \DateTime::createFromFormat('Y-m-d', (string) $value);
        $errors = \DateTime::getLastErrors();

        // PHP 8.2 returns false instead of an array when there are no warnings.
        $hasErrors = is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0);

        return $date !== false && !$hasErrors && $date->format('Y-m-d') === $value;
    }

    private function isStrongPassword($value)
    {
        return preg_match('/[A-Za-z]/', $value) === 1
            && preg_match('/[0-9]/', $value) === 1
            && $this->length($value) >= 8;
    }

    private function normalise($value)
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return trim($value);
        }

        return $value;
    }

    /** Turn 'job_type' into 'Job type' for error messages. */
    private function label($field)
    {
        $words = str_replace('_', ' ', $field);

        return ucfirst(trim($words));
    }

    public function fails()
    {
        return !empty($this->errors);
    }

    public function passes()
    {
        return empty($this->errors);
    }

    /** @return array field => string[] */
    public function errors()
    {
        return $this->errors;
    }

    public function firstError()
    {
        foreach ($this->errors as $messages) {
            return $messages[0];
        }

        return 'The submitted data was not valid.';
    }

    /** @return array field => string[] A flat list for flash messages */
    public function errorMessages()
    {
        $flat = [];

        foreach ($this->errors as $messages) {
            foreach ($messages as $message) {
                $flat[] = $message;
            }
        }

        return $flat;
    }

    /** Validated and trimmed values, ready to be written to the database. */
    public function validated()
    {
        return $this->clean;
    }

    /** A single validated value. */
    public function value($field, $default = null)
    {
        return array_key_exists($field, $this->clean) ? $this->clean[$field] : $default;
    }
}
