<?php

declare(strict_types=1);

use B13\Container\Tca\ContainerConfiguration;
use B13\Container\Tca\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

defined('TYPO3') or die();

$ll = 'LLL:EXT:ot_sitekitbase/Resources/Private/Language/locallang_db.xlf:';

/**
 * # Bootstrap Card Groups
 * @see https://getbootstrap.com/docs/5.3/components/card/#grid-cards
 */
//<editor-fold desc="Container Extension Configuration: Card Groups">
GeneralUtility::makeInstance(Registry::class)->configureContainer(
    (
    new ContainerConfiguration(
        'ot-sitekit-base-container-card-group',
        'Card Group',
        'Bootstrap 5 Card Group',
        [
            [
                [
                    'name' => 'Card Groups',
                    'colPos' => 300,
                    'allowed' => [
                        'CType' => 'ot_sitekitcecard',
                    ],
                ],
            ],
        ]
    )
    )
        ->setIcon('EXT:container/Resources/Public/Icons/container-4col.svg')
);

$GLOBALS['TCA']['tt_content']['types']['ot-sitekit-base-container-card-group']['columnsOverrides']['ot_layout']['label'] = $ll . 'ot_layout';
$GLOBALS['TCA']['tt_content']['types']['ot-sitekit-base-container-card-group']['columnsOverrides']['ot_layout']['config']['items'] = [
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

$GLOBALS['TCA']['tt_content']['types']['ot-sitekit-base-container-card-group']['showitem'] = str_replace(
    'tabs.appearance,',
    'tabs.appearance,ot_layout,',
    $GLOBALS['TCA']['tt_content']['types']['ot-sitekit-base-container-card-group']['showitem']
);

//</editor-fold>
