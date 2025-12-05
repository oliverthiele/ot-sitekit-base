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

namespace OliverThiele\OtSitekitbase\Backend\EventListener;

use TYPO3\CMS\Backend\Controller\Event\ModifyPageLayoutContentEvent;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;

final readonly class PagePropertiesAboveContentListener
{
    public function __construct(
        private FileRepository $fileRepository,
        private ViewFactoryInterface $viewFactory
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
