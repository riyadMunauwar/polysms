<?php

namespace Riyad\PolySms\DTO;

use JsonSerializable;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionProperty;

/**
 * Class BaseDTO
 *
 * Abstract base Data Transfer Object (DTO) class.
 * 
 * Provides:
 * - Filling properties from an array
 * - Validation of required public properties
 * - Support for dynamic extra properties
 * - Array and JSON serialization
 */
#[\AllowDynamicProperties] // PHP 8.2+ to allow dynamic properties
abstract class BaseDTO implements JsonSerializable
{
    /** @var array<string, mixed> Dynamic extra properties */
    protected array $extra = [];

    /**
     * Constructor: fill DTO properties from array and validate required fields.
     *
     * @param array<string, mixed> $data Initial data to populate the DTO
     *
     * @throws InvalidArgumentException If required properties are missing
     */
    public function __construct(array $data = [])
    {
        $this->fill($data, true);
    }

    /**
     * Fill DTO properties from an array.
     *
     * Existing properties are updated. Any keys not matching a class property
     * are stored in the `extra` array.
     *
     * @param array<string, mixed> $data Data to fill
     * @param bool $checkRequired Whether to validate required properties after filling
     * @return static
     *
     * @throws InvalidArgumentException If required properties are missing and $checkRequired is true
     */
    public function fill(array $data, bool $checkRequired = false): static
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            } else {
                $this->extra[$key] = $value;
            }
        }

        if ($checkRequired) {
            $this->validateRequiredProperties();
        }

        return $this;
    }

    /**
     * Validate that all required public properties are set.
     *
     * This method checks all public properties of the current object and ensures
     * that they have a value. Properties are considered required if:
     *   - They do not have a default value.
     *   - They are not nullable (e.g., `?string`) or of type `mixed`.
     *
     * If any required property is missing, an `InvalidArgumentException` is thrown.
     *
     * @throws InvalidArgumentException If a required property is not set.
     */
    protected function validateRequiredProperties(): void
    {
        $reflection = new ReflectionClass($this);

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
            $name = $prop->getName();

            // Skip properties that have a default value
            if ($prop->hasDefaultValue()) {
                continue;
            }

            $type = $prop->getType();

            // Skip if type allows null or is mixed
            if ($type !== null) {
                if ($type->allowsNull() || $type->getName() === 'mixed') {
                    continue;
                }
            }

            // Check if the property is set
            if (!isset($this->{$name})) {
                throw new InvalidArgumentException("Required property '{$name}' is missing");
            }
        }
    }

    /**
     * Magic getter for extra properties.
     *
     * @param string $name Property name
     * @return mixed|null Value if exists, otherwise null
     */
    public function __get(string $name): mixed
    {
        return $this->extra[$name] ?? null;
    }

    /**
     * Magic setter for properties.
     *
     * @param string $name Property name
     * @param mixed $value Value to set
     */
    public function __set(string $name, mixed $value): void
    {
        if (property_exists($this, $name)) {
            $this->$name = $value;
        } else {
            $this->extra[$name] = $value;
        }
    }

    /**
     * Magic isset for properties.
     *
     * @param string $name Property name
     * @return bool True if property exists or is set in extra, false otherwise
     */
    public function __isset(string $name): bool
    {
        return property_exists($this, $name) || isset($this->extra[$name]);
    }

    /**
     * Magic unset for properties.
     *
     * @param string $name Property name
     */
    public function __unset(string $name): void
    {
        if (property_exists($this, $name)) {
            $this->$name = null;
        } else {
            unset($this->extra[$name]);
        }
    }

    /**
     * Convert DTO to array.
     *
     * Includes defined non-null properties and any extra properties.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $static = array_filter(
            get_object_vars($this),
            fn ($value) => $value !== null
        );

        return array_merge($static, $this->extra);
    }

    /**
     * Specify data for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}