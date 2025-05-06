<?php

defined('TYPO3') or die();

use OliverThiele\OtSitekitbase\Backend\Preview\GenericPreviewRenderer;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

$ll = 'LLL:EXT:ot_sitekitbase/Resources/Private/Language/locallang_db.xlf:';

$tempColumns = [
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
                    'value' => ''
                ],
                [
                    'label' => $ll . 'header_style.h1',
                    'value' => 'h1',
                    'group' => 'groupHeader',
                    'icon' => 'ot-icon-h1-regular'
                ],
                [
                    'label' => $ll . 'header_style.h2',
                    'value' => 'h2',
                    'group' => 'groupHeader',
                    'icon' => 'ot-icon-h2-regular'
                ],
                [
                    'label' => $ll . 'header_style.h3',
                    'value' => 'h3',
                    'group' => 'groupHeader',
                    'icon' => 'ot-icon-h3-regular'
                ],
                [
                    'label' => $ll . 'header_style.h4',
                    'value' => 'h4',
                    'group' => 'groupHeader',
                    'icon' => 'ot-icon-h4-regular'
                ],
                [
                    'label' => $ll . 'header_style.h5',
                    'value' => 'h5',
                    'group' => 'groupHeader',
                    'icon' => 'ot-icon-h5-regular'
                ],
                [
                    'label' => $ll . 'header_style.h6',
                    'value' => 'h6',
                    'group' => 'groupHeader',
                    'icon' => 'ot-icon-h6-regular'
                ],
                [
                    'label' => 'Display 1',
                    'value' => 'display-1',
                    'group' => 'groupDisplay'
                ],
                [
                    'label' => 'Display 2',
                    'value' => 'display-2',
                    'group' => 'groupDisplay'
                ],
                [
                    'label' => 'Display 3',
                    'value' => 'display-3',
                    'group' => 'groupDisplay'
                ],
                [
                    'label' => 'Display 4',
                    'value' => 'display-4',
                    'group' => 'groupDisplay'
                ],
                [
                    'label' => 'Display 5',
                    'value' => 'display-5',
                    'group' => 'groupDisplay'
                ],
                [
                    'label' => 'Display 6',
                    'value' => 'display-6',
                    'group' => 'groupDisplay'
                ],
                [
                    'label' => $ll . 'header_style.visuallyHidden',
                    'value' => 'visually-hidden',
                    'group' => 'groupSpecial',
                    'icon' => 'ot-icon-universal-access-regular'

                ]
            ],
            'itemGroups' => [
                'groupHeader' => $ll . 'header_style.groupHeader',
                'groupDisplay' => $ll . 'header_style.groupDisplay',
                'groupSpecial' => $ll . 'header_style.groupSpecial',
            ],
            'size' => 1,
            'maxitems' => 1,
        ]
    ],
];

$headerItems = [
    1 => ['label' => $ll . 'header_layout.h1', 'value' => '1', 'icon' => 'ot-icon-h1-regular'],
    2 => ['label' => $ll . 'header_layout.h2', 'value' => '2', 'icon' => 'ot-icon-h2-regular'],
    3 => ['label' => $ll . 'header_layout.h3', 'value' => '3', 'icon' => 'ot-icon-h3-regular'],
    4 => ['label' => $ll . 'header_layout.h4', 'value' => '4', 'icon' => 'ot-icon-h4-regular'],
    5 => ['label' => $ll . 'header_layout.h5', 'value' => '5', 'icon' => 'ot-icon-h5-regular']
];

foreach ($headerItems as $key => $item) {
    $GLOBALS['TCA']['tt_content']['columns']['header_layout']['config']['items'][$key] = $item;
}

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
