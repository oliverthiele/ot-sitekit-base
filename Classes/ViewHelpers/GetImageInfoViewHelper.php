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

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class GetImageInfoViewHelper extends AbstractViewHelper implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var bool
     */
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument(
            'image',
            'mixed',
            'Path to file or FileReference object',
            true
        );
        $this->registerArgument(
            'data',
            'array',
            'The tt_content data array for crop variants',
            false,
            []
        );
        $this->registerArgument(
            'defaultCropVariant',
            'string',
            'Fallback crop variant identifier',
            false,
            'default'
        );
        $this->registerArgument(
            'maxWidth',
            'mixed',
            'Explicit width in pixels (int or array)',
            false,
            null
        );
        $this->registerArgument(
            'numColumns',
            'mixed',
            'Number of columns to divide container width by (int or array)',
            false,
            null
        );
        $this->registerArgument(
            'gridColumns',
            'mixed',
            'Bootstrap grid columns (1-12) to calculate width (int or array).
                       Overrides numColumns but can be overridden by maxWidth.',
            false,
            null
        );
    }

    /**
     * @return array{
     *     exists: bool,
     *     publicUrl: string,
     *     variants: array<string, string>,
     *     widths: array<string, int>,
     *     suggestPictureTag: bool,
     *     ratioClass: string,
     *     aspectRatioGroups: array<int, array{
     *         aspectRatio: string,
     *         ratioClass: string,
     *         breakpoints: array<string>,
     *         displayClass: string,
     *         cropVariant: string,
     *         srcsetEntries: array<int, array{width: int, breakpoint: string}>
     *     }>,
     *     metadata: array{
     *         uid: int,
     *         alternative: string,
     *         title: string,
     *         description: string,
     *         link: string
     *     },
     *     original: mixed
     * }
     */
    public function render(): array
    {
        $image = $this->arguments['image'];
        $data = $this->arguments['data'];
        $defaultCrop = $this->arguments['defaultCropVariant'];
        $maxWidthInput = $this->arguments['maxWidth'];
        $numColumnsInput = $this->arguments['numColumns'];
        $gridColumnsInput = $this->arguments['gridColumns'];

        $result = [
            'exists' => false,
            'publicUrl' => '',
            'variants' => [],
            'widths' => [],
            'suggestPictureTag' => false,
            'ratioClass' => '',
            'aspectRatioGroups' => [],
            'isSvg' => false,
            'metadata' => [
                'uid' => 0,
                'alternative' => '',
                'title' => '',
                'description' => '',
                'link' => '',
            ],
            'original' => $image,
        ];

        if (empty($image)) {
            return $result;
        }

        try {
            if ($image instanceof FileReference) {
                $originalFile = $image->getOriginalFile();
                if (!$originalFile->exists()) {
                    $this->logger?->warning('Referenced file is missing for FileReference UID: ' . $image->getUid());
                    return $result;
                }
                $result['publicUrl'] = (string)($image->getPublicUrl() ?? '');
                $result['metadata']['uid'] = $image->getUid();
                $result['metadata']['alternative'] = $image->getAlternative();
                $result['metadata']['title'] = $image->getTitle();
                $result['metadata']['description'] = $image->getDescription();
                $result['metadata']['link'] = $image->getLink();

                // Check if it's an SVG
                $mimeType = $image->getMimeType();
                if (str_starts_with($mimeType, 'image/svg')) {
                    $result['isSvg'] = true;
                }
            } elseif (is_string($image)) {
                // Check if it's an SVG (for string paths)
                if (str_ends_with(strtolower($image), '.svg')) {
                    $result['isSvg'] = true;
                }
                $absPath = GeneralUtility::getFileAbsFileName($image);
                if (empty($absPath) || !file_exists($absPath)) {
                    $this->logger?->warning('File not found at path: ' . $image);
                    return $result;
                }
                $result['publicUrl'] = $image;
            } else {
                return $result;
            }
        } catch (\Throwable $e) {
            $this->logger?->error('Error resolving image: ' . $e->getMessage());
            return $result;
        }

        $result['exists'] = true;

        // 2. Width calculation (Smart Inheritance & Columns)
        $breakpoints = ['xs', 'sm', 'md', 'lg', 'xl', 'xxl'];

        $defaultBootstrapWidths = [
            'xs' => 551,
            'sm' => 516,
            'md' => 696,
            'lg' => 936,
            'xl' => 1116,
            'xxl' => 1296,
        ];

        // Normalise inputs
        $inputWidths = [];
        if (is_array($maxWidthInput)) {
            $inputWidths = $maxWidthInput;
        } elseif (is_numeric($maxWidthInput) || is_string($maxWidthInput)) {
            $inputWidths = ['xs' => (int)$maxWidthInput];
        }

        $inputCols = [];
        if (is_array($numColumnsInput)) {
            $inputCols = $numColumnsInput;
        } elseif (is_numeric($numColumnsInput)) {
            $inputCols = ['xs' => (int)$numColumnsInput];
        }

        $inputGridCols = [];
        if (is_array($gridColumnsInput)) {
            $inputGridCols = $gridColumnsInput;
        } elseif (is_numeric($gridColumnsInput)) {
            $inputGridCols = ['xs' => (int)$gridColumnsInput];
        }

        // State tracking for inheritance
        // Priority: maxWidth > gridColumns > numColumns
        $currentColCount = 1;
        $currentGridColCount = null;
        $currentPixelOverride = null;

        foreach ($breakpoints as $bp) {
            // 1. Check for numColumns definition
            if (isset($inputCols[$bp]) && $inputCols[$bp] > 0) {
                $currentColCount = (float)$inputCols[$bp];
                $currentGridColCount = null;
                $currentPixelOverride = null;
            }

            // 2. Check for gridColumns definition (overrides numColumns)
            if (isset($inputGridCols[$bp]) && $inputGridCols[$bp] > 0) {
                $currentGridColCount = (float)$inputGridCols[$bp];
                $currentPixelOverride = null;
            }

            // 3. Check for pixel definition (overrides everything)
            if (isset($inputWidths[$bp]) && $inputWidths[$bp] > 0) {
                $currentPixelOverride = (int)$inputWidths[$bp];
            }

            // 4. Calculation for this breakpoint
            if ($currentPixelOverride !== null) {
                $result['widths'][$bp] = $currentPixelOverride;
            } elseif ($currentGridColCount !== null) {
                // Calculate based on Bootstrap grid columns (1-12)
                $result['widths'][$bp] = (int)ceil($defaultBootstrapWidths[$bp] * ($currentGridColCount / 12));
            } else {
                // Calculate based on number of columns (divide container)
                $result['widths'][$bp] = (int)ceil($defaultBootstrapWidths[$bp] / $currentColCount);
            }
        }

        // 3. Crop variants & ratio calculation
        $currentVariant = $defaultCrop;
        $ratiosFound = [];

        foreach ($breakpoints as $bp) {
            $fieldName = 'crop_variant_' . $bp;
            if (!empty($data[$fieldName])) {
                $currentVariant = $data[$fieldName];
            }
            $result['variants'][$bp] = $currentVariant;

            if (str_contains((string)$currentVariant, ':')) {
                $ratiosFound[] = $currentVariant;
            } else {
                $ratiosFound[] = 'free';
            }
        }

        // Picture Tag Decision
        // As soon as we have different crops -> Picture Tag
        $uniqueVariants = array_unique($result['variants']);
        if (count($uniqueVariants) > 1) {
            $result['suggestPictureTag'] = true;
        }

        // Ratio Class Decision
        // Only if ALL breakpoints have the same ratio
        $uniqueRatios = array_unique($ratiosFound);
        if (count($uniqueRatios) === 1 && $uniqueRatios[0] !== 'free') {
            // Wandelt z.B. 16:9 in 16x9 um für Bootstrap Ratio Klasse
            $ratioString = str_replace(':', 'x', $uniqueRatios[0]);
            $result['ratioClass'] = 'ratio ratio-' . $ratioString;
        }

        // 4. Aspect Ratio Grouping for optimized img-tags
        // Group breakpoints by their aspect ratio and crop variant
        $result['aspectRatioGroups'] = $this->buildAspectRatioGroups(
            $breakpoints,
            $result['variants'],
            $result['widths'],
            $ratiosFound
        );

        return $result;
    }

    /**
     * Groups breakpoints by aspect ratio and creates optimized srcset entries
     *
     * @param array<string> $breakpoints
     * @param array<string, string> $variants
     * @param array<string, int> $widths
     * @param array<string> $ratiosFound
     * @return array<int, array{
     *     aspectRatio: string,
     *     ratioClass: string,
     *     breakpoints: array<string>,
     *     displayClass: string,
     *     cropVariant: string,
     *     srcsetEntries: array<int, array{width: int, breakpoint: string}>
     * }>
     */
    private function buildAspectRatioGroups(
        array $breakpoints,
        array $variants,
        array $widths,
        array $ratiosFound
    ): array {
        $groups = [];
        $currentGroup = null;
        $bpMinWidths = ['xs' => 0, 'sm' => 576, 'md' => 768, 'lg' => 992, 'xl' => 1200, 'xxl' => 1400];

        foreach ($breakpoints as $index => $bp) {
            $cropVariant = $variants[$bp];
            $aspectRatio = $ratiosFound[$index];

            // Check if we need to start a new group (different crop or aspect ratio)
            if (
                $currentGroup === null ||
                $currentGroup['cropVariant'] !== $cropVariant ||
                $currentGroup['aspectRatio'] !== $aspectRatio
            ) {
                // Finalize previous group if exists
                if ($currentGroup !== null) {
                    $groups[] = $currentGroup;
                }

                // Start new group
                $ratioClass = '';
                if ($aspectRatio !== 'free') {
                    $ratioClass = 'ratio ratio-' . str_replace(':', 'x', $aspectRatio);
                }

                $currentGroup = [
                    'aspectRatio' => $aspectRatio,
                    'ratioClass' => $ratioClass,
                    'breakpoints' => [$bp],
                    'displayClass' => '',
                    'cropVariant' => $cropVariant,
                    'srcsetEntries' => [],
                ];
            } else {
                // Add to current group
                $currentGroup['breakpoints'][] = $bp;
            }
        }

        // Add last group
        if ($currentGroup !== null) {
            $groups[] = $currentGroup;
        }

        // Calculate display classes for each group
        $groupCount = count($groups);
        foreach ($groups as $i => &$group) {
            $firstBp = $group['breakpoints'][0];
            $lastBp = $group['breakpoints'][count($group['breakpoints']) - 1];

            if ($groupCount === 1) {
                // Only one group: always visible
                $group['displayClass'] = '';
            } elseif ($i === 0) {
                // First group: hide from next group's first breakpoint
                $nextGroupFirstBp = $groups[$i + 1]['breakpoints'][0];
                $group['displayClass'] = 'd-' . $nextGroupFirstBp . '-none';
            } elseif ($i === $groupCount - 1) {
                // Last group: show from this group's first breakpoint
                $group['displayClass'] = 'd-none d-' . $firstBp . '-block';
            } else {
                // Middle group: show from first bp, hide from next group's first bp
                $nextGroupFirstBp = $groups[$i + 1]['breakpoints'][0];
                $group['displayClass'] = 'd-none d-' . $firstBp . '-block d-' . $nextGroupFirstBp . '-none';
            }

            // Build optimized srcset entries (remove near-duplicates)
            $group['srcsetEntries'] = $this->buildOptimizedSrcset($group['breakpoints'], $widths);
        }

        return $groups;
    }

    /**
     * Builds optimized srcset entries by removing near-duplicate widths
     *
     * @param array<string> $breakpoints
     * @param array<string, int> $widths
     * @return array<int, array{width: int, breakpoint: string}>
     */
    private function buildOptimizedSrcset(array $breakpoints, array $widths): array
    {
        $entries = [];
        $threshold = 0.15; // 15% difference threshold

        foreach ($breakpoints as $bp) {
            $width = $widths[$bp];
            $shouldAdd = true;

            // Check if this width is too similar to an existing one
            foreach ($entries as $entry) {
                $diff = abs($width - $entry['width']) / $entry['width'];
                if ($diff < $threshold) {
                    $shouldAdd = false;
                    break;
                }
            }

            if ($shouldAdd) {
                $entries[] = [
                    'width' => $width,
                    'breakpoint' => $bp,
                ];
            }
        }

        return $entries;
    }
}
