<?php

declare(strict_types=1);

namespace LeisPet\Homepage\Model\Config;

use Magento\Framework\Exception\LocalizedException;

class WidgetConfigValidator
{
    public function __construct(
        private readonly WidgetTypeSchemaRegistry $schemaRegistry
    ) {
    }

    /**
     * Validate and normalize config JSON for a given widget type.
     *
     * @return array<string, mixed>
     */
    public function validateAndNormalize(string $widgetType, ?string $configJson): array
    {
        $widgetType = trim($widgetType);

        if (!in_array($widgetType, $this->schemaRegistry->getSupportedTypes(), true)) {
            throw new LocalizedException(__('Unsupported widget type: %1', $widgetType));
        }

        if ($configJson === null || trim($configJson) === '') {
            return [];
        }

        $decoded = json_decode($configJson, true);
        if (!is_array($decoded)) {
            throw new LocalizedException(__('Configuration JSON must be a valid JSON object.'));
        }

        // Normalize legacy CTA to canonical format
        if ($widgetType === 'cta') {
            $decoded = $this->schemaRegistry->normalizeLegacyCta($decoded);
        }

        $schema = $this->schemaRegistry->getSchema($widgetType);
        $this->validateFieldsAgainstSchema($decoded, $schema);

        return $decoded;
    }

    /**
     * Validate decoded config data against schema field definitions.
     *
     * @param array<string, mixed> $data
     * @param array<int, array<string, mixed>> $schema
     */
    private function validateFieldsAgainstSchema(array $data, array $schema): void
    {
        foreach ($schema as $field) {
            $path = $field['path'] ?? '';
            $required = $field['required'] ?? false;
            $type = $field['type'] ?? 'text';

            if ($type === 'repeatable') {
                $this->validateRepeatableField($data, $field);
                continue;
            }

            $value = $this->getNestedValue($data, $path);

            if ($required && ($value === null || (is_string($value) && trim($value) === ''))) {
                $label = $field['label'] ?? $path;
                throw new LocalizedException(__('Missing required field: %1', $label));
            }

            if ($value !== null) {
                $this->validateFieldType($value, $type, $field['label'] ?? $path);
            }
        }
    }

    /**
     * Validate a repeatable (array-of-objects) field and its children.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $field
     */
    private function validateRepeatableField(array $data, array $field): void
    {
        $path = $field['path'] ?? '';
        $required = $field['required'] ?? false;
        $children = $field['children'] ?? [];
        $items = $data[$path] ?? null;

        if ($required && (empty($items) || !is_array($items))) {
            throw new LocalizedException(__('Missing required field: %1 (must be a non-empty array)', $field['label'] ?? $path));
        }

        if (!is_array($items)) {
            return;
        }

        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }

            foreach ($children as $child) {
                $childPath = $child['path'] ?? '';
                $childRequired = $child['required'] ?? false;
                $childValue = $item[$childPath] ?? null;

                if ($childRequired && ($childValue === null || (is_string($childValue) && trim($childValue) === ''))) {
                    throw new LocalizedException(
                        __('Missing required field "%1" in %2 item #%3', $child['label'] ?? $childPath, $field['label'] ?? $path, $index + 1)
                    );
                }

                if ($childValue !== null) {
                    $this->validateFieldType($childValue, $child['type'] ?? 'text', ($child['label'] ?? $childPath) . " (item #" . ($index + 1) . ")");
                }
            }
        }
    }

    /**
     * Get a nested value from an array using dot-notation path.
     */
    private function getNestedValue(array $data, string $path): mixed
    {
        $keys = explode('.', $path);
        $current = $data;

        foreach ($keys as $key) {
            if (!is_array($current) || !array_key_exists($key, $current)) {
                return null;
            }
            $current = $current[$key];
        }

        return $current;
    }

    /**
     * Validate that a value matches the expected schema type.
     */
    private function validateFieldType(mixed $value, string $type, string $label): void
    {
        $valid = match ($type) {
            'text', 'textarea', 'url', 'image' => is_string($value) || is_numeric($value),
            'number' => is_numeric($value),
            'boolean' => is_bool($value) || in_array($value, [0, 1, '0', '1'], true),
            'select' => is_string($value) || is_numeric($value),
            default => true,
        };

        if (!$valid) {
            throw new LocalizedException(
                __('Invalid type for field "%1". Expected %2.', $label, $type)
            );
        }
    }
}
