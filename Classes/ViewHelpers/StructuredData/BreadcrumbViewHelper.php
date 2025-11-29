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

namespace OliverThiele\OtSitekitbase\ViewHelpers\StructuredData;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;

/**
 * Class BreadcrumbViewHelper
 */
final class BreadcrumbViewHelper extends AbstractViewHelper
{
    protected $escapeOutput = false;

    /**
     * Initialize arguments.
     *
     * @throws Exception
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('value', 'string', 'string to format');
        $this->registerArgument('addScriptTag', 'bool', 'add script tag');
    }

    /**
     * @return string
     */
    public function render(): string
    {
        $value = $this->arguments['value'];
        $addScriptTag = (bool)$this->arguments['addScriptTag'];

        if ($value === null) {
            $value = $this->renderChildren();
        }

        if (!is_array($value)) {
            return '';
        }

        $httpScheme = $_SERVER['HTTP_SCHEME'] ?? null;

        if (
            $httpScheme === 'https' ||
            ($_SERVER['HTTPS'] ?? '') === 'on' ||
            ($_SERVER['HTTPS'] ?? '') === '1' ||
            ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
        ) {
            $scheme = 'https://';
        } else {
            $scheme = 'http://';
        }

        $itemListElement = [];
        $i = 1;

        foreach ($value as $item) {
            $itemListElement[] = [
                '@type' => 'ListItem',
                'position' => $i,
                'item' => [
                    '@id' => $scheme . ($_SERVER['HTTP_HOST'] ?? '') . $item['link'],
                    'name' => $item['title'],
                ],
            ];
            $i++;
        }

        $data = [
            '@context' => 'http://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $itemListElement,
        ];

        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        if ($addScriptTag === true) {
            $json = '<script type="application/ld+json">' . PHP_EOL . $json . PHP_EOL . '</script>' . PHP_EOL;
        }

        return $json;
    }
}
