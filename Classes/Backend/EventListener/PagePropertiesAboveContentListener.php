<?php

declare(strict_types=1);

namespace OliverThiele\OtSitekitbase\Backend\EventListener;

use TYPO3\CMS\Backend\Controller\Event\ModifyPageLayoutContentEvent;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Core\View\ViewFactoryData;


final class PagePropertiesAboveContentListener
{
    public function __construct(
        private readonly FileRepository $fileRepository,
        private readonly ViewFactoryInterface $viewFactory
    ) {
    }

    public function __invoke(ModifyPageLayoutContentEvent $event): void
    {
        // Determine current page ID
        $pageId = (int)($event->getRequest()->getQueryParams()['id'] ?? 0);
        if ($pageId === 0) {
            return;
        }

        // Retrieve page data
        $pageRecord = BackendUtility::getRecord('pages', $pageId);
        if (empty($pageRecord)) {
            return;
        }

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $returnUrl = (string)$uriBuilder->buildUriFromRoute('web_layout', [
            'id' => $pageId,
        ]);
        $editLink = (string)$uriBuilder->buildUriFromRoute('record_edit', [
            'edit' => ['pages' => [$pageId => 'edit']],
            'returnUrl' => $returnUrl,
        ]);

        // Load FileReferences from field 'media'
        $images = $this->fileRepository->findByRelation('pages', 'media', $pageId);

        // Initialize view configuration
        $viewFactoryData = new ViewFactoryData(
            templateRootPaths: ['EXT:ot_sitekitbase/Resources/Private/Backend/Templates/PageModule/'],
            partialRootPaths: ['EXT:ot_sitekitbase/Resources/Private/Backend/Partials/'],
            layoutRootPaths: ['EXT:ot_sitekitbase/Resources/Private/Backend/Layouts/'],
            templatePathAndFilename: 'EXT:ot_sitekitbase/Resources/Private/Backend/PageModule/PageHeader.html'
        );

        // Create view
        $view = $this->viewFactory->create($viewFactoryData);
        $view->assignMultiple([
            'page' => $pageRecord,
            'images' => $images,
            'editLink' => $editLink,
        ]);

        // Add header content
        $event->addHeaderContent($view->render());
    }
}
