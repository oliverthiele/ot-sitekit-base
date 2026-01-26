<?php

declare(strict_types=1);

use B13\Container\Tca\ContainerConfiguration;
use B13\Container\Tca\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

$ll = 'LLL:EXT:ot_sitekitbase/Resources/Private/Language/locallang_db.xlf:';

/**
 * # Width 80 %
 */
//<editor-fold desc="Container Extension Configuration: Width 80 %">
GeneralUtility::makeInstance(Registry::class)->configureContainer(
    (
    new ContainerConfiguration(
        'ot-sitekit-base-container-layout-width-80',
        $ll . 'container.ot-sitekit-base-container-layout-width-80.CType.label',
        $ll . 'container.ot-sitekit-base-container-layout-width-80.CType.description',
        [
            [
                [
                    'name' => $ll . 'container.ot-sitekit-base-container-layout-width-80.colPos.100',
                    'colPos' => 100,
//                    'allowed' => [
//                        'CType' => '*',
//                    ],
                ],
            ],
        ]
    )
    )
        ->setIcon('EXT:container/Resources/Public/Icons/container-1col.svg')
);

/**
 * # Width 60 %
 */

//<editor-fold desc="Container Extension Configuration: Width 60 %">
GeneralUtility::makeInstance(Registry::class)->configureContainer(
    (
    new ContainerConfiguration(
        'ot-sitekit-base-container-layout-width-60',
        $ll . 'container.ot-sitekit-base-container-layout-width-60.CType.label',
        $ll . 'container.ot-sitekit-base-container-layout-width-60.CType.description',
        [
            [
                [
                    'name' => $ll . 'container.ot-sitekit-base-container-layout-width-60.colPos.100',
                    'colPos' => 100,
//                    'allowed' => [
//                        'CType' => '*',
//                    ],
                ],
            ],
        ]
    )
    )
        ->setIcon('EXT:container/Resources/Public/Icons/container-1col.svg')
);
