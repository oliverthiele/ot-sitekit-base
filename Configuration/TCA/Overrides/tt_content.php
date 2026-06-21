<?php

declare(strict_types=1);

defined('TYPO3') or die();

use OliverThiele\OtSitekitbase\Backend\Preview\GenericPreviewRenderer;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

$ll = 'LLL:EXT:ot_sitekitbase/Resources/Private/Language/locallang_db.xlf:';

/**
 * # Load Container Extension Configuration
 */
require_once GeneralUtility::getFileAbsFileName(
    'EXT:ot_sitekitbase/Configuration/TCA/Container/CardGroup.php'
);
require_once GeneralUtility::getFileAbsFileName(
    'EXT:ot_sitekitbase/Configuration/TCA/Container/GridCards.php'
);
require_once GeneralUtility::getFileAbsFileName(
    'EXT:ot_sitekitbase/Configuration/TCA/Container/Columns.php'
);
require_once GeneralUtility::getFileAbsFileName(
    'EXT:ot_sitekitbase/Configuration/TCA/Container/Layouts.php'
);
require_once GeneralUtility::getFileAbsFileName(
    'EXT:ot_sitekitbase/Configuration/TCA/Container/BgColor.php'
);

//<editor-fold desc="Additional containers groups">

ExtensionManagementUtility::addTcaSelectItemGroup(
    'tt_content',
    'CType',
    'pageContainer',
    $ll . 'container.pageContainer',
    'after:list'
);

ExtensionManagementUtility::addTcaSelectItemGroup(
    'tt_content',
    'CType',
    'container1Col',
    $ll . 'container.1col',
    'after:list'
);

ExtensionManagementUtility::addTcaSelectItemGroup(
    'tt_content',
    'CType',
    'container2Cols',
    $ll . 'container.2cols',
    'after:list'
);

ExtensionManagementUtility::addTcaSelectItemGroup(
    'tt_content',
    'CType',
    'container3Cols',
    $ll . 'container.3cols',
    'after:list'
);

ExtensionManagementUtility::addTcaSelectItemGroup(
    'tt_content',
    'CType',
    'container4Cols',
    $ll . 'container.4cols',
    'after:list'
);

ExtensionManagementUtility::addTcaSelectItemGroup(
    'tt_content',
    'CType',
    'containerSpecial',
    $ll . 'container.special',
    'after:list'
);

ExtensionManagementUtility::addTcaSelectItemGroup(
    'tt_content',
    'CType',
    'containerBgColor',
    $ll . 'container.bgcolor.group',
    'after:list'
);
//</editor-fold>

$ceWithResponsiveImages = [
    'OR' => [
        'FIELD:CType:=:ot_sitekitcetextmedia',
        'FIELD:CType:=:ot_sitekitcecard',
    ],
];

$cropVariants = [
    ['label' => $ll . 'cropVariants.0-xs', 'value' => ''],
    ['label' => $ll . 'cropVariants.1', 'value' => 'org'],
    ['label' => $ll . 'cropVariants.2', 'value' => 'free'],
    ['label' => '1:1', 'value' => '1:1'],
    ['label' => '16:9', 'value' => '16:9'],
    ['label' => '4:3', 'value' => '4:3'],
    ['label' => '3:2', 'value' => '3:2'],
    ['label' => '3:4', 'value' => '3:4'],
    ['label' => '2:3', 'value' => '2:3'],
    // ['label' => 'Hero', 'value' => 'hero'], // Used in EXT:ot_heroimage
];

$tempColumns = [
    'ot_layout' => [
        'exclude' => true,
        'label' => $ll . 'ot_layout',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => [
                [
                    'label' => $ll . 'ot_layout.default',
                    'value' => '',
                ],
            ],
        ],
    ],
    'ot_text_columns' => [
        'exclude' => true,
        'label' => $ll . 'ot_text_columns',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => [
                ['label' => $ll . 'ot_text_columns.singleColumn', 'value' => ''],
                ['label' => $ll . 'ot_text_columns.multiColumn', 'value' => 'auto-column-text'],
            ],
            'size' => 1,
        ],
    ],
    'header_style' => [
        'exclude' => true,
        'label' => $ll . 'header_style',
        'description' => $ll . 'header_style.description',
        'displayCond' => 'FIELD:header_layout:!=:100',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => [
                [
                    'label' => $ll . 'header_style.default',
                    'value' => '',
                ],
                [
                    'label' => $ll . 'header_style.h1',
                    'value' => 'h1',
                    'group' => 'groupHeader',
                    'icon' => 'ot-icon-h1-regular',
                ],
                [
                    'label' => $ll . 'header_style.h2',
                    'value' => 'h2',
                    'group' => 'groupHeader',
                    'icon' => 'ot-icon-h2-regular',
                ],
                [
                    'label' => $ll . 'header_style.h3',
                    'value' => 'h3',
                    'group' => 'groupHeader',
                    'icon' => 'ot-icon-h3-regular',
                ],
                [
                    'label' => $ll . 'header_style.h4',
                    'value' => 'h4',
                    'group' => 'groupHeader',
                    'icon' => 'ot-icon-h4-regular',
                ],
                [
                    'label' => $ll . 'header_style.h5',
                    'value' => 'h5',
                    'group' => 'groupHeader',
                    'icon' => 'ot-icon-h5-regular',
                ],
                [
                    'label' => $ll . 'header_style.h6',
                    'value' => 'h6',
                    'group' => 'groupHeader',
                    'icon' => 'ot-icon-h6-regular',
                ],
                [
                    'label' => 'Display 1',
                    'value' => 'display-1',
                    'group' => 'groupDisplay',
                ],
                [
                    'label' => 'Display 2',
                    'value' => 'display-2',
                    'group' => 'groupDisplay',
                ],
                [
                    'label' => 'Display 3',
                    'value' => 'display-3',
                    'group' => 'groupDisplay',
                ],
                [
                    'label' => 'Display 4',
                    'value' => 'display-4',
                    'group' => 'groupDisplay',
                ],
                [
                    'label' => 'Display 5',
                    'value' => 'display-5',
                    'group' => 'groupDisplay',
                ],
                [
                    'label' => 'Display 6',
                    'value' => 'display-6',
                    'group' => 'groupDisplay',
                ],
                [
                    'label' => $ll . 'header_style.visuallyHidden',
                    'value' => 'visually-hidden',
                    'group' => 'groupSpecial',
                    'icon' => 'ot-icon-universal-access-regular',
                ],
            ],
            'itemGroups' => [
                'groupHeader' => $ll . 'header_style.groupHeader',
                'groupDisplay' => $ll . 'header_style.groupDisplay',
                'groupSpecial' => $ll . 'header_style.groupSpecial',
            ],
            'size' => 1,
            'maxitems' => 1,
        ],
    ],
    'crop_variant_xs' => [
        'exclude' => true,
        'label' => $ll . 'cropVariantXs',
        'displayCond' => $ceWithResponsiveImages,
        'onChange' => 'reload',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => $cropVariants,
            'size' => 1,
            'maxitems' => 1,
        ],
    ],
    'crop_variant_sm' => [
        'exclude' => true,
        'label' => $ll . 'cropVariantSm',
        'displayCond' => $ceWithResponsiveImages,
        'onChange' => 'reload',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => $cropVariants,
            'size' => 1,
            'maxitems' => 1,
        ],
    ],
    'crop_variant_md' => [
        'exclude' => true,
        'label' => $ll . 'cropVariantMd',
        'displayCond' => $ceWithResponsiveImages,
        'onChange' => 'reload',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => $cropVariants,
            'size' => 1,
            'maxitems' => 1,
        ],
    ],
    'crop_variant_lg' => [
        'exclude' => true,
        'label' => $ll . 'cropVariantLg',
        'displayCond' => $ceWithResponsiveImages,
        'onChange' => 'reload',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => $cropVariants,
            'size' => 1,
            'maxitems' => 1,
        ],
    ],
    'crop_variant_xl' => [
        'exclude' => true,
        'label' => $ll . 'cropVariantXl',
        'displayCond' => $ceWithResponsiveImages,
        'onChange' => 'reload',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => $cropVariants,
            'size' => 1,
            'maxitems' => 1,
        ],
    ],
    'crop_variant_xxl' => [
        'exclude' => true,
        'label' => $ll . 'cropVariantXxl',
        'displayCond' => $ceWithResponsiveImages,
        'onChange' => 'reload',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => $cropVariants,
            'size' => 1,
            'maxitems' => 1,
        ],
    ],
];

$headerItems = [
    1 => ['label' => $ll . 'header_layout.h1', 'value' => '1', 'icon' => 'ot-icon-h1-regular'],
    2 => ['label' => $ll . 'header_layout.h2', 'value' => '2', 'icon' => 'ot-icon-h2-regular'],
    3 => ['label' => $ll . 'header_layout.h3', 'value' => '3', 'icon' => 'ot-icon-h3-regular'],
    4 => ['label' => $ll . 'header_layout.h4', 'value' => '4', 'icon' => 'ot-icon-h4-regular'],
    5 => ['label' => $ll . 'header_layout.h5', 'value' => '5', 'icon' => 'ot-icon-h5-regular'],
];

foreach ($headerItems as $key => $item) {
    $GLOBALS['TCA']['tt_content']['columns']['header_layout']['config']['items'][$key] = $item;
}

$GLOBALS['TCA']['tt_content']['columns']['header_layout']['config']['default'] = 2;
$GLOBALS['TCA']['tt_content']['columns']['header_layout']['onChange'] = 'reload';

$GLOBALS['TCA']['tt_content']['columns']['header_position']['displayCond'] = 'FIELD:header_layout:!=:100';

ExtensionManagementUtility::addTCAcolumns('tt_content', $tempColumns);

ExtensionManagementUtility::addFieldsToPalette(
    'tt_content',
    'header',
    'header_style',
    'after:header_layout'
);

ExtensionManagementUtility::addFieldsToPalette(
    'tt_content',
    'headers',
    'header_style',
    'after:header_layout'
);

$GLOBALS['TCA']['tt_content']['palettes']['ot-crop-variants'] = [
    'label' => $ll . 'palette.ot-crop-variants.label',
    'description' => $ll . 'palette.ot-crop-variants.description',
    'showitem' => 'crop_variant_xs, crop_variant_sm, crop_variant_md, crop_variant_lg, crop_variant_xl, crop_variant_xxl',
];

$GLOBALS['TCA']['tt_content']['columns']['crop_variant_xs']['config']['items'][0]['label'] = $ll . 'cropVariants.0';

// Own generic preview renderer
foreach (
    [
        'div',
        'header',
        'html',
        'list',
        'ot_cefluidtemplates',
        'ot_responsiveimages',
        'text',
        'textmedia',
    ] as $cType
) {
    $GLOBALS['TCA']['tt_content']['types'][$cType]['previewRenderer'] = GenericPreviewRenderer::class;
}

// Add ot_text_columns to the content types text and textmedia
ExtensionManagementUtility::addToAllTCAtypes(
    'tt_content',
    'ot_text_columns',
    'text,textmedia',
    'after:bodytext'
);
