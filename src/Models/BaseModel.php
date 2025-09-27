<?php

declare(strict_types=1);

namespace AS2aaS\Models;

use DateTime;
use JsonSerializable;

/**
 * Base model class
 */
abstract class BaseModel implements JsonSerializable
{
    protected array $attributes = [];
    protected array $fillable = [];
    protected array $casts = [];

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    /**
     * Fill model with attributes
     */
    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if (empty($this->fillable) || in_array($key, $this->fillable)) {
                $this->setAttribute($key, $value);
            }
        }

        return $this;
    }

    /**
     * Set attribute with casting
     */
    public function setAttribute(string $key, $value): self
    {
        if (isset($this->casts[$key])) {
            $value = $this->castAttribute($key, $value);
        }

        $this->attributes[$key] = $value;
        return $this;
    }

    /**
     * Get attribute
     */
    public function getAttribute(string $key)
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Cast attribute to specified type
     */
    protected function castAttribute(string $key, $value)
    {
        $cast = $this->casts[$key];

        return match ($cast) {
            'boolean', 'bool' => (bool) $value,
            'integer', 'int' => (int) $value,
            'float', 'double' => (float) $value,
            'string' => (string) $value,
            'array' => is_array($value) ? $value : json_decode($value, true),
            'object' => is_object($value) ? $value : json_decode($value),
            'datetime' => $this->asDateTime($value),
            default => $value,
        };
    }

    /**
     * Convert value to DateTime
     */
    protected function asDateTime($value): ?DateTime
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTime) {
            return $value;
        }

        if (is_string($value)) {
            return new DateTime($value);
        }

        if (is_int($value)) {
            return new DateTime('@' . $value);
        }

        return null;
    }

    /**
     * Get all attributes
     */
    public function toArray(): array
    {
        $array = [];

        foreach ($this->attributes as $key => $value) {
            if ($value instanceof DateTime) {
                $array[$key] = $value->format('c');
            } else {
                $array[$key] = $value;
            }
        }

        return $array;
    }

    /**
     * JSON serialization
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Convert to JSON
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Magic getter
     */
    public function __get(string $key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Magic setter
     */
    public function __set(string $key, $value): void
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Magic isset
     */
    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }
}
