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

namespace OliverThiele\OtSitekitbase\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Renders <source> elements for a <video> tag with responsive media queries.
 *
 * The media query for each source is calculated from the Bootstrap column context
 * and the element-width threshold stored in each source entry (resolutionWidth).
 *
 * The last source (lowest resolution or unknown) is always rendered without a
 * media attribute so the browser always has a fallback.
 *
 * Usage:
 *   <sk:videoSources sources="{video.sources}" columns="6" stackingBreakpoint="md"/>
 *
 * columns can be:
 *   - integer (e.g. 6): collapses to 12 below stackingBreakpoint, uses given value above
 *   - array   (e.g. {xs: 12, md: 6, lg: 4}): mobile-first per-breakpoint definition
 */
class VideoSourcesViewHelper extends AbstractViewHelper
{
    protected $escapeOutput = false;

    /** Bootstrap viewport min-widths in px */
    private const VIEWPORT_WIDTHS = [
        'xs'  => 0,
        'sm'  => 576,
        'md'  => 768,
        'lg'  => 992,
        'xl'  => 1200,
        'xxl' => 1400,
    ];

    /**
     * Bootstrap container max-widths in px.
     * null = no fixed container (full viewport width).
     * Override via $containerWidths argument for custom Bootstrap configs or full-viewport layouts.
     */
    private const DEFAULT_CONTAINER_WIDTHS = [
        'xs'  => null,
        'sm'  => 540,
        'md'  => 720,
        'lg'  => 960,
        'xl'  => 1140,
        'xxl' => 1320,
    ];

    private const BREAKPOINT_ORDER = ['xs', 'sm', 'md', 'lg', 'xl', 'xxl'];

    public function initializeArguments(): void
    {
        $this->registerArgument(
            'sources',
            'array',
            'Sources array from VideoProcessor (keys: url, type, resolutionWidth)',
            true
        );
        $this->registerArgument(
            'columns',
            'mixed',
            'Bootstrap columns (int 1–12 for all breakpoints above stacking, or array per breakpoint)',
            false,
            12
        );
        $this->registerArgument(
            'stackingBreakpoint',
            'string',
            'Below this breakpoint columns collapse to 12 (mobile stacking). Only used when columns is an integer.',
            false,
            'md'
        );
        $this->registerArgument(
            'containerWidths',
            'array',
            'Override Bootstrap container max-widths per breakpoint (e.g. for full-viewport or custom containers).',
            false,
            []
        );
    }

    public function render(): string
    {
        $sources = $this->arguments['sources'];
        if (empty($sources) || !is_array($sources)) {
            return '';
        }

        $containerWidths = array_merge(
            self::DEFAULT_CONTAINER_WIDTHS,
            is_array($this->arguments['containerWidths']) ? $this->arguments['containerWidths'] : []
        );

        $columnsPerBreakpoint = $this->resolveColumnsPerBreakpoint(
            $this->arguments['columns'],
            (string)$this->arguments['stackingBreakpoint']
        );

        $output = '';
        $lastIndex = count($sources) - 1;

        foreach ($sources as $index => $source) {
            $url  = htmlspecialchars((string)($source['url'] ?? ''), ENT_QUOTES, 'UTF-8');
            $type = htmlspecialchars((string)($source['type'] ?? ''), ENT_QUOTES, 'UTF-8');
            $resolutionWidth = isset($source['resolutionWidth'])
                ? (int)$source['resolutionWidth']
                : null;

            $isLast = ($index === $lastIndex);

            if ($isLast || $resolutionWidth === null) {
                // Lowest or unknown resolution: always serve as fallback, no media attribute
                $output .= sprintf('<source src="%s" type="%s">', $url, $type) . "\n";
                continue;
            }

            $minViewport = $this->calculateMinViewport($resolutionWidth, $columnsPerBreakpoint, $containerWidths);

            if ($minViewport === null) {
                // This resolution is never needed at the given column configuration — skip
                continue;
            }

            $output .= sprintf(
                '<source src="%s" type="%s" media="(min-width: %dpx)">',
                $url,
                $type,
                $minViewport
            ) . "\n";
        }

        return $output;
    }

    /**
     * Expands the columns argument into a per-breakpoint map.
     *
     * Integer input: applies the value at and above stackingBreakpoint, 12 below it.
     * Array input: mobile-first cascade — missing breakpoints inherit the previous value.
     *
     * @param mixed $columns
     * @return array<string, int>
     */
    private function resolveColumnsPerBreakpoint(mixed $columns, string $stackingBreakpoint): array
    {
        if (is_int($columns) || (is_string($columns) && ctype_digit((string)$columns))) {
            $targetColumns = max(1, min(12, (int)$columns));
            $resolved = [];
            $stacking = true;
            foreach (self::BREAKPOINT_ORDER as $breakpoint) {
                if ($breakpoint === $stackingBreakpoint) {
                    $stacking = false;
                }
                $resolved[$breakpoint] = $stacking ? 12 : $targetColumns;
            }
            return $resolved;
        }

        if (is_array($columns)) {
            $resolved = [];
            $lastValue = 12;
            foreach (self::BREAKPOINT_ORDER as $breakpoint) {
                if (isset($columns[$breakpoint])) {
                    $lastValue = max(1, min(12, (int)$columns[$breakpoint]));
                }
                $resolved[$breakpoint] = $lastValue;
            }
            return $resolved;
        }

        // Fallback: full width across all breakpoints
        return array_fill_keys(self::BREAKPOINT_ORDER, 12);
    }

    /**
     * Finds the minimum viewport width (px) at which the video element would be
     * at least $resolutionWidth pixels wide, given the Bootstrap column context.
     *
     * Returns null when the resolution is never needed (element always stays smaller).
     *
     * @param array<string, int>      $columnsPerBreakpoint
     * @param array<string, int|null> $containerWidths
     */
    private function calculateMinViewport(
        int $resolutionWidth,
        array $columnsPerBreakpoint,
        array $containerWidths
    ): ?int {
        foreach (self::BREAKPOINT_ORDER as $breakpoint) {
            $containerWidth = $containerWidths[$breakpoint] ?? null;
            if ($containerWidth === null) {
                // xs: no fixed container — element width ≈ viewport (rarely needs HD)
                continue;
            }

            $effectiveColumns = $columnsPerBreakpoint[$breakpoint] ?? 12;
            $elementWidth = (int)round($containerWidth * $effectiveColumns / 12);

            if ($elementWidth >= $resolutionWidth) {
                return self::VIEWPORT_WIDTHS[$breakpoint];
            }
        }

        return null;
    }
}
