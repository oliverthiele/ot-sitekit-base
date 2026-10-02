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

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Renders the breadcrumb as a schema.org BreadcrumbList in JSON-LD, from the
 * items of a rootline menu (MenuProcessor with special = rootline).
 *
 * The item the menu marks as the current page is rendered without a URL:
 * Google takes the URL of the page itself then. On the detail view of a
 * plugin, the current page in the rootline is the detail page — its own URL,
 * without the record, would link a page that shows a list or an error. A page
 * the rootline menu leaves out, e.g. one inside a folder, keeps every URL.
 *
 *     <sk:structuredData.breadcrumb value="{menuBreadcrumb}" addScriptTag="true"/>
 */
final class BreadcrumbViewHelper extends AbstractViewHelper
{
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('value', 'array', 'The items of a rootline menu, each with "title" and "link".');
        $this->registerArgument('addScriptTag', 'bool', 'Wrap the JSON in a <script type="application/ld+json"> tag.', false, false);
    }

    public function getContentArgumentName(): string
    {
        return 'value';
    }

    public function render(): string
    {
        $items = $this->renderChildren();
        if (!is_array($items) || $items === []) {
            return '';
        }

        $baseUri = $this->getBaseUri();
        $itemListElement = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $title = $item['title'] ?? '';
            $listItem = [
                '@type' => 'ListItem',
                'position' => count($itemListElement) + 1,
                'name' => is_string($title) ? $title : '',
            ];
            $link = $item['link'] ?? '';
            $isCurrentPage = in_array($item['current'] ?? null, [1, '1', true], true);
            if (!$isCurrentPage && is_string($link) && $link !== '') {
                $listItem['item'] = $baseUri === null ? $link : (string)UriResolver::resolve($baseUri, new Uri($link));
            }
            $itemListElement[] = $listItem;
        }

        try {
            // JSON_HEX_TAG: a title containing "</script>" must not end the script block.
            $json = json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => $itemListElement,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return '';
        }

        return $this->arguments['addScriptTag'] === true
            ? '<script type="application/ld+json">' . $json . '</script>'
            : $json;
    }

    /**
     * The URL of the current request; the relative links of the menu are
     * resolved against it. Null outside a request, e.g. on the command line.
     */
    private function getBaseUri(): ?Uri
    {
        $renderingContext = $this->renderingContext;
        if ($renderingContext === null || !$renderingContext->hasAttribute(ServerRequestInterface::class)) {
            return null;
        }
        $request = $renderingContext->getAttribute(ServerRequestInterface::class);

        return $request instanceof ServerRequestInterface ? new Uri((string)$request->getUri()) : null;
    }
}
