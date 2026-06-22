<?php

declare(strict_types=1);

use B13\Container\Tca\ContainerConfiguration;
use B13\Container\Tca\Registry;
use OliverThiele\OtSitekitbase\SiteKit\SiteKitRegistry;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;

defined('TYPO3') or die();

$ll = 'LLL:EXT:ot_sitekitbase/Resources/Private/Language/locallang_db.xlf:';

$siteKitRegistry = GeneralUtility::makeInstance(SiteKitRegistry::class);
$CTypesFullContainerWidth = $siteKitRegistry->getCTypesForGroups(['group_content_wide']);
$CTypesAdvanced = $siteKitRegistry->getCTypesForGroups(['group_advanced']);

$typo3MajorVersion = (new Typo3Version())->getMajorVersion();
$restrictionBgColor = $typo3MajorVersion >= 14
    ? ['allowedContentTypes' => $siteKitRegistry->mergeCTypes($CTypesFullContainerWidth, $CTypesAdvanced)]
    : ['allowed' => ['CType' => $siteKitRegistry->mergeCTypes($CTypesFullContainerWidth, $CTypesAdvanced)], 'disallowed' => ['CType' => '']];

$bgColorContainers = [
    [
        'ctype' => 'ot-sitekit-base-container-bgcolor-primary',
        'label' => $ll . 'container.bgcolor.primary.CType.label',
        'description' => $ll . 'container.bgcolor.primary.CType.description',
        'colPosLabel' => $ll . 'container.bgcolor.primary.colPos.100',
    ],
    [
        'ctype' => 'ot-sitekit-base-container-bgcolor-secondary',
        'label' => $ll . 'container.bgcolor.secondary.CType.label',
        'description' => $ll . 'container.bgcolor.secondary.CType.description',
        'colPosLabel' => $ll . 'container.bgcolor.secondary.colPos.100',
    ],
    [
        'ctype' => 'ot-sitekit-base-container-bgcolor-light',
        'label' => $ll . 'container.bgcolor.light.CType.label',
        'description' => $ll . 'container.bgcolor.light.CType.description',
        'colPosLabel' => $ll . 'container.bgcolor.light.colPos.100',
    ],
    [
        'ctype' => 'ot-sitekit-base-container-bgcolor-dark',
        'label' => $ll . 'container.bgcolor.dark.CType.label',
        'description' => $ll . 'container.bgcolor.dark.CType.description',
        'colPosLabel' => $ll . 'container.bgcolor.dark.colPos.100',
    ],
];

foreach ($bgColorContainers as $container) {
    GeneralUtility::makeInstance(Registry::class)->configureContainer(
        (
        new ContainerConfiguration(
            $container['ctype'],
            $container['label'],
            $container['description'],
            [
                [
                    [
                        'name' => $container['colPosLabel'],
                        'colPos' => 100,
                        'colspan' => 1,
                        ...$restrictionBgColor,
                    ],
                ],
            ]
        )
        )
            ->setIcon('EXT:container/Resources/Public/Icons/container-1col.svg')
            ->setGroup('containerBgColor')
    );
}
