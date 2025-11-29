<?php

declare(strict_types=1);

/**
 * Copyright notice
 *
 * (c) 2025 Oliver Thiele <mail@oliver-thiele.de>, Web Development Oliver Thiele
 * All rights reserved
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * This copyright notice MUST APPEAR in all copies of the script!
 */

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
