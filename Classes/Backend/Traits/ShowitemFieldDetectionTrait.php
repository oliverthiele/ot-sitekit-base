<?php

declare(strict_types=1);

namespace OliverThiele\OtSitekitbase\Backend\Traits;

trait ShowitemFieldDetectionTrait
{
    /**
     * Extracts and returns the list of unique fields used in the 'showitem' configuration for a specific content type.
     *
     * @param string $cType The content type identifier for which the 'showitem' configuration should be processed.
     * @return array The array of unique field names extracted from the 'showitem' configuration.
     */
    protected function getUsedFieldsFromShowitem(string $cType): array
    {
        $showitem = $GLOBALS['TCA']['tt_content']['types'][$cType]['showitem'] ?? '';
        $items = explode(',', $showitem);
        $fields = [];

        foreach ($items as $item) {
            $item = trim($item);
            if (str_starts_with($item, '--')) {
                continue;
            }

            $parts = explode(';', $item, 2);
            $field = trim($parts[0]);

            if ($field !== '') {
                $fields[] = $field;
            }
        }

        return array_unique($fields);
    }

    protected function isFieldUsedInShowitem(string $cType, string $fieldName): bool
    {
        return in_array($fieldName, $this->getUsedFieldsFromShowitem($cType), true);
    }
}
