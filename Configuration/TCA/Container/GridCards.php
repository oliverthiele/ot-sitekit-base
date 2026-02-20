<?php

declare(strict_types=1);

use B13\Container\Tca\ContainerConfiguration;
use B13\Container\Tca\Registry;
use OliverThiele\OtSitekitbase\SiteKit\SiteKitRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

$ll = 'LLL:EXT:ot_sitekitbase/Resources/Private/Language/locallang_db.xlf:';

$siteKitRegistry = GeneralUtility::makeInstance(SiteKitRegistry::class);

/**
 * # Grid Cards
 *
 * Bootstrap Grid Cards
 * @see https://getbootstrap.com/docs/5.3/components/card/#grid-cards
 *
 * This container can be used also for content element "Text & Icon"
 */
//<editor-fold desc="Container Extension Configuration: Grid Cards">
GeneralUtility::makeInstance(Registry::class)->configureContainer(
    (
    new ContainerConfiguration(
        'ot-sitekit-base-container-grid-cards',
        $ll . 'container.ot-sitekit-base-container-grid-cards.CType.label',
        $ll . 'container.ot-sitekit-base-container-grid-cards.CType.description',
        [
            [
                [
                    'name' => $ll . 'container.ot-sitekit-base-container-grid-cards.colPos.300', //Grid Cards',
                    'colPos' => 300,
                    'allowed' => [
                        'CType' => $siteKitRegistry->getCTypesForGroups(['group_cards']),
                    ],
                ],
            ],
        ]
    )
    )
//        ->setIcon('EXT:ot_sitekitbase/Resources/Public/Icons/Container/Container_4col.svg')
        ->setIcon('EXT:container/Resources/Public/Icons/container-4col.svg')
);

$GLOBALS['TCA']['tt_content']['types']['ot-sitekit-base-container-grid-cards']['columnsOverrides']['ot_layout']['label'] = $ll . 'ot_layout';
$GLOBALS['TCA']['tt_content']['types']['ot-sitekit-base-container-grid-cards']['columnsOverrides']['ot_layout']['config']['items'] = [
    0 => [
        'value' => '',
        'label' => $ll . 'container.ot-sitekit-base-container-grid-cards.ot_layout.0', // 'Standard (Maximal Vierspaltig)',
    ],
    1 => [
        'value' => '1',
        'label' => $ll . 'container.ot-sitekit-base-container-grid-cards.ot_layout.1', //  'Maximal Zweispaltig',
    ],
    2 => [
        'value' => '2',
        'label' => $ll . 'container.ot-sitekit-base-container-grid-cards.ot_layout.2', // 'Maximal Dreispaltig',
    ],
    3 => [
        'value' => '3',
        'label' => $ll . 'container.ot-sitekit-base-container-grid-cards.ot_layout.3', // 'Maximal Vierspaltig',
    ],
];
// Show the header layout field for the parentElement array
// Label replacement not working yet
$GLOBALS['TCA']['tt_content']['types']['ot-sitekit-base-container-grid-cards']['showitem'] = str_replace(
    'header;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header.ALT.div_formlabel,',
    'header;LLL:EXT:ot_sitekitbase/Resources/Private/Language/locallang_db.xlf:grid_cards_container,header_layout,',
    $GLOBALS['TCA']['tt_content']['types']['ot-sitekit-base-container-grid-cards']['showitem']
);

$GLOBALS['TCA']['tt_content']['types']['ot-sitekit-base-container-grid-cards']['showitem'] = str_replace(
    'tabs.appearance,',
    'tabs.appearance,ot_layout,',
    $GLOBALS['TCA']['tt_content']['types']['ot-sitekit-base-container-grid-cards']['showitem']
);
//</editor-fold>
