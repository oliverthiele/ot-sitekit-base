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

namespace OliverThiele\OtSitekitbase\Backend\Preview;

use OliverThiele\OtSitekitbase\Backend\Traits\ShowitemFieldDetectionTrait;
use TYPO3\CMS\Backend\Preview\PreviewRendererInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumnItem;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;

final class GenericPreviewRenderer implements PreviewRendererInterface
{
    use ShowitemFieldDetectionTrait;

    public function renderPageModulePreviewHeader(GridColumnItem $item): string
    {
        // No TYPO3 header – these are rendered in the individual templates using partial templates.
        return '';
    }

    public function __construct(
        private readonly ViewFactoryInterface $viewFactory,
        private readonly FileRepository $fileRepository
    ) {
    }

    public function renderPageModulePreviewContent(GridColumnItem $item): string
    {
        $record = $item->getRecord();

        $request = ServerRequestFactory::fromGlobals();
        /** @var UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);

        $editUri = $uriBuilder->buildUriFromRoute(
            'record_edit',
            [
                'edit' => [
                    'tt_content' => [
                        (int)$record['uid'] => 'edit',
                    ],
                ],
                'returnUrl' => (string)$request->getUri(),
            ]
        );

        $cType = $record['CType'];

        $templateFile = $this->getTemplateFileNameForCType($cType);
        $templatePath = GeneralUtility::getFileAbsFileName(
            'EXT:ot_sitekitbase/Resources/Private/Backend/ContentElements/' . $templateFile
        );

        if (!is_file($templatePath)) {
            $templatePath = GeneralUtility::getFileAbsFileName(
                'EXT:ot_sitekitbase/Resources/Private/Backend/ContentElements/Default.html'
            );
        }

        // Initialize view configuration
        $viewFactoryData = new ViewFactoryData(
            templateRootPaths: ['EXT:ot_sitekitbase/Resources/Private/Backend/Templates/PageModule/'],
            partialRootPaths: ['EXT:ot_sitekitbase/Resources/Private/Backend/Partials/'],
            layoutRootPaths: ['EXT:ot_sitekitbase/Resources/Private/Backend/Layouts/'],
            templatePathAndFilename: $templatePath
        );

        // Create view
        $view = $this->viewFactory->create($viewFactoryData);

        $assets = [];
        if ($this->isFieldUsedInShowitem($cType, 'assets')) {
            $assets = $this->fileRepository->findByRelation('tt_content', 'assets', $record['uid']);
        }

        $images = [];
        if ($this->isFieldUsedInShowitem($cType, 'image')) {
            $images = $this->fileRepository->findByRelation('tt_content', 'image', $record['uid']);
        }

        $view->assignMultiple([
            'record' => $record,
            'assets' => $assets,
            'images' => $images,
            'editUri' => (string)$editUri,
        ]);

        return $view->render();
    }

    public function renderPageModulePreviewFooter(GridColumnItem $item): string
    {
        $record = $item->getRecord();

        switch ($record['header_layout']) {
            case '100':
                $headerIndicator = '<span class="badge text-bg-dark me-3">' . 'Header disabled' . '</span>';
                break;
            case '0':
                $headerIndicator = '<span class="badge text-bg-info me-3">' . 'H2' . '</span>';
                break;
            default:
                $headerIndicator = '<span class="badge text-bg-primary me-3">H' . $record['header_layout'] . '</span>';
        }

        return '<div class="text-muted small">' . $headerIndicator .
            'UID: ' . $record['uid'] .
            ' &middot; CType: ' . $record['CType'] .
            '</div>';
    }

    public function wrapPageModulePreview(string $previewHeader, string $previewContent, GridColumnItem $item): string
    {
        return '<div class="ot-preview-wrapper">' . $previewHeader . $previewContent . '</div>';
    }

    private function getTemplateFileNameForCType(string $cType): string
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $cType))) . '.html';
    }

    private function isFieldUsedInShowitem(string $cType, string $fieldName): bool
    {
        $fields = $this->getUsedFieldsFromShowitem($cType);
        return in_array($fieldName, $fields, true);
    }
}
