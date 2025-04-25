<?php

declare(strict_types=1);

namespace OliverThiele\OtSitekitbase\Backend\Preview;

use TYPO3\CMS\Backend\Preview\PreviewRendererInterface;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumnItem;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use OliverThiele\OtSitekitbase\Backend\Traits\ShowitemFieldDetectionTrait;
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
        if (!$this->viewFactory instanceof ViewFactoryInterface) {
            throw new \RuntimeException('ViewFactoryInterface not properly injected');
        }
        if (!$this->fileRepository instanceof FileRepository) {
            throw new \RuntimeException('FileRepository not properly injected');
        }
    }


    public function renderPageModulePreviewContent(GridColumnItem $item): string
    {
        $record = $item->getRecord();
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

        $view->assignMultiple([
            'record' => $record,
            'assets' => $assets,
        ]);

        return $view->render();
    }

    public function renderPageModulePreviewFooter(GridColumnItem $item): string
    {
        $record = $item->getRecord();

        return '<div class="text-muted small">UID: ' . $record['uid'] . ' &middot; CType: ' . $record['CType'] . '</div>';
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
