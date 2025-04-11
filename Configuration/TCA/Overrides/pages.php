<?php

defined('TYPO3') or die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

$ll = 'LLL:EXT:ot_sitekitbase/Resources/Private/Language/locallang_db.xlf:';

$tempColumns = [
    'icon_identifier' => [
        'exclude' => true,
        'label' => $ll . 'pages.iconIdentifier.label',
//        'description' => $ll . 'pages.iconIdentifier.description', // 'This icon can be displayed in the navigation.',
        'config' => [
            'type' => 'input',
        ],
    ],
    'menu_image' => [
        'exclude' => true,
        'label' => $ll . 'pages.menuImage.label',
//        'description' => $ll . 'pages.menuImage.description',
        'config' => [
            'type' => 'file',
            'appearance' => [
                'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference',
            ],
            'overrideChildTca' => [
                'columns' => [
                    'uid_local' => [
                        'config' => [
                            'appearance' => [
                                'elementBrowserAllowed' => 'jpg,jpeg,png,gif,svg,webp,avif',
                                'elementBrowserType' => 'file',
                            ],
                        ],
                    ],
                ],
            ],
            'maxitems' => 1,
        ],
    ],
];
ExtensionManagementUtility::addTCAcolumns('pages', $tempColumns);

ExtensionManagementUtility::addToAllTCAtypes(
    'pages',
    ', --div--;Layout, menu_image, icon_identifier',
    '',
    'after:categories'
);
