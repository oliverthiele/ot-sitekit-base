<?php

declare(strict_types=1);

use B13\Container\Tca\ContainerConfiguration;
use B13\Container\Tca\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

$ll = 'LLL:EXT:ot_sitekitbase/Resources/Private/Language/locallang_db.xlf:';

$CTypesForSmallerColumns = '
    header,
    text,
    menu_subpages,
    div,
    felogin_login,
    form_formframework,
    list,
    ot_cefluidtemplates,
    ot_sitekitceimgtextoverlay,
    ot_sitekitcetexticon,
    ot_sitekitcetextmedia,';

$CTypesForBackendLayoutAdvanced = '
    ot-sitekit-base-container-1-col-container,
    ot-sitekit-base-container-1-col-container-fluid,
    ot-sitekit-base-container-1-col-bgcolor,
    ot-sitekit-base-container-1-col-article,
    ot-sitekit-base-container-1-col-section,';

$CTypesFullContainerWidth =
    $CTypesForSmallerColumns . '
    ot_jobs,
    otfaq_list,
    ot-sitekit-base-container-grid-cards,
    ot-sitekit-base-container-1-col-article,
    ot-sitekit-base-container-1-col-section,
    ot-sitekit-base-container-2-cols-50-50,
    ot-sitekit-base-container-2-cols-33-66,
    ot-sitekit-base-container-2-cols-66-33,
    ot-sitekit-base-container-3-cols-33-33-33,';

$GLOBALS['TCA']['tt_content']['types']['ot-sitekit-base-container-card-group']['columnsOverrides']['ot_layout']['label'] = $ll . 'ot_layout';
$GLOBALS['TCA']['tt_content']['types']['ot-sitekit-base-container-card-group']['columnsOverrides']['ot_layout']['config']['items'] = [
    0 => [
        'value' => '',
        'label' => $ll . 'container.ot-sitekit-base-container-1-col-bgcolor.ot_layout.0', // 'Standard (Primary)',
    ],
    1 => [
        'value' => 'secondary',
        'label' => $ll . 'container.ot-sitekit-base-container-1-col-bgcolor.ot_layout.1',
    ],
    2 => [
        'value' => 'light',
        'label' => $ll . 'container.ot-sitekit-base-container-1-col-bgcolor.ot_layout.2',
    ],
    3 => [
        'value' => 'dark',
        'label' => $ll . 'container.ot-sitekit-base-container-1-col-bgcolor.ot_layout.3',
    ],
];

// todo fix non working showitem
//if (isset($GLOBALS['TCA']['tt_content']['types']['ot-sitekit-base-container-1-col-bgcolor']['showitem'])) {
//    $GLOBALS['TCA']['tt_content']['types']['ot-sitekit-base-container-1-col-bgcolor']['showitem'] = str_replace(
//        'tabs.appearance,',
//        'tabs.appearance,ot_layout,',
//        $GLOBALS['TCA']['tt_content']['types']['ot-sitekit-base-container-1-col-bgcolor']['showitem']
//    );
//}

/**
 * 1 Column (only for Backend Layout "Advanced")
 */

/**
 * Bootstrap .container
 */
//<editor-fold desc="Container Extension Configuration: 1 Column Container with class container">
GeneralUtility::makeInstance(Registry::class)->configureContainer(
    (
    new ContainerConfiguration(
        'ot-sitekit-base-container-1-col-container',
        'Container',
        'max. 1400px container',
        [
            [
                [
                    'name' => 'BT Container',
                    'colPos' => 100,
                    'allowed' => [
                        'CType' => $CTypesFullContainerWidth,
                    ],
                    'disallowed' => [
                        'CType' => '',
                    ],
                ],
            ],
        ]
    )
    )
        ->setIcon('EXT:container/Resources/Public/Icons/container-1col.svg')
);
//</editor-fold>

/**
 * Bootstrap .container-fluid
 */
//<editor-fold desc="Container Extension Configuration: 1 Column Container with class container-fluid">
GeneralUtility::makeInstance(Registry::class)->configureContainer(
    (
    new ContainerConfiguration(
        'ot-sitekit-base-container-1-col-container-fluid',
        'Container (fluid)',
        'Full width container',
        [
            [
                [
                    'name' => 'BT Container Fluid',
                    'colPos' => 100,
                    'allowed' => [
                        'CType' => $CTypesFullContainerWidth,
                    ],
                    'disallowed' => [
                        'CType' => '',
                    ],
                ],
            ],
        ]
    )
    )
        ->setIcon('EXT:container/Resources/Public/Icons/container-1col.svg')
);
//</editor-fold>

/**
 * Container for <div> with background-color
 */
//<editor-fold desc="Container Extension Configuration: 1 Column Container with background-color">
GeneralUtility::makeInstance(Registry::class)->configureContainer(
    (
    new ContainerConfiguration(
        'ot-sitekit-base-container-1-col-bgcolor',
        'Container <div> with background-color',
        'This container changes the background color.',
        [
            [
                [
                    'name' => 'Container with background-color',
                    'colPos' => 100,
                    'allowed' => [
                        'CType' => $CTypesFullContainerWidth .
                            'ot-sitekit-base-container-1-col-container, ot-sitekit-base-container-1-col-container-fluid,',
                    ],
                    'disallowed' => [
                        'CType' => '',
                    ],
                ],
            ],
        ]
    )
    )
        ->setIcon('EXT:container/Resources/Public/Icons/container-1col.svg')
    //        ->setBackendTemplate($extKey . '/Resources/Private/Templates/Container/Backend/CardDeck.html')
);
//</editor-fold>

/**
 * Container for <article>
 */
//<editor-fold desc="Container Extension Configuration: 1 Column Container">
GeneralUtility::makeInstance(Registry::class)->configureContainer(
    (
    new ContainerConfiguration(
        'ot-sitekit-base-container-1-col-article',
        'Container <article>',
        'An <article> tag is used to enclose a self-contained piece of content that could stand alone or be distributed independently, such as a blog post, news story, or similar.',
        [
            [
                [
                    'name' => 'Article',
                    'colPos' => 100,
                    'allowed' => [
                        'CType' => $CTypesFullContainerWidth,
                    ],
                    'disallowed' => [
                        'CType' => '',
                    ],
                ],
            ],
        ]
    )
    )
        ->setIcon('EXT:container/Resources/Public/Icons/container-1col.svg')
    //        ->setBackendTemplate($extKey . '/Resources/Private/Templates/Container/Backend/CardDeck.html')
);
//</editor-fold>

/**
 * Container for <section>
 */
//<editor-fold desc="Container Extension Configuration: 1 Column Container">
GeneralUtility::makeInstance(Registry::class)->configureContainer(
    (
    new ContainerConfiguration(
        'ot-sitekit-base-container-1-col-section',
        'Container <section>',
        'A <section> is used in HTML to structure a thematically related area of content that can stand alone and make sense on its own.',
        [
            [
                [
                    'name' => 'Section',
                    'colPos' => 100,
                    'allowed' => [
                        'CType' => $CTypesFullContainerWidth,
                    ],
                    'disallowed' => [
                        'CType' => '',
                    ],
                ],
            ],
        ]
    )
    )
        ->setIcon('EXT:container/Resources/Public/Icons/container-1col.svg')
    //        ->setBackendTemplate($extKey . '/Resources/Private/Templates/Container/Backend/CardDeck.html')
);
//</editor-fold>

/**
 * 2 Columns
 */
//<editor-fold desc="Container Extension Configuration: 2 columns 50 % / 50 %>
GeneralUtility::makeInstance(Registry::class)->configureContainer(
    (
    new ContainerConfiguration(
        'ot-sitekit-base-container-2-cols-50-50',
        '2 Columns 50 % / 50 %',
        '',
        [
            [
                [
                    'name' => 'Left',
                    'colPos' => 200,
                    'allowed' => [
                        'CType' => $CTypesForSmallerColumns,
                    ],
                ],
                [
                    'name' => 'Right',
                    'colPos' => 201,
                    'allowed' => [
                        'CType' => $CTypesForSmallerColumns,
                    ],
                ],
            ],
        ]
    )
    )
        ->setIcon('EXT:container/Resources/Public/Icons/container-2col.svg')
);
//</editor-fold>

//<editor-fold desc="Container Extension Configuration: 2 columns 33 % / 66 %>
GeneralUtility::makeInstance(Registry::class)->configureContainer(
    (
    new ContainerConfiguration(
        'ot-sitekit-base-container-2-cols-33-66',
        '2 Columns 33 % / 66 %',
        '',
        [
            [
                [
                    'name' => 'Left',
                    'colPos' => 200,
                    'allowed' => [
                        'CType' => $CTypesForSmallerColumns,
                    ],
                ],
                [
                    'name' => 'Right',
                    'colPos' => 201,
                    'allowed' => [
                        'CType' => $CTypesForSmallerColumns,
                    ],
                ],
            ],
        ]
    )
    )
        ->setIcon('EXT:container/Resources/Public/Icons/container-2col-right.svg')
);
//</editor-fold>

//<editor-fold desc="Container Extension Configuration: 2 columns 66 % / 33 %>
GeneralUtility::makeInstance(Registry::class)->configureContainer(
    (
    new ContainerConfiguration(
        'ot-sitekit-base-container-2-cols-66-33',
        '2 Columns 66 % / 33 %',
        '',
        [
            [
                [
                    'name' => 'Left',
                    'colPos' => 200,
                    'allowed' => [
                        'CType' => $CTypesForSmallerColumns,
                    ],
                ],
                [
                    'name' => 'Right',
                    'colPos' => 201,
                    'allowed' => [
                        'CType' => $CTypesForSmallerColumns,
                    ],
                ],
            ],
        ]
    )
    )
        ->setIcon('EXT:container/Resources/Public/Icons/container-2col-left.svg')
);
//</editor-fold>

/**
 * 3 Columns
 */
//<editor-fold desc="Container Extension Configuration: 3 columns">
GeneralUtility::makeInstance(Registry::class)->configureContainer(
    (
    new ContainerConfiguration(
        'ot-sitekit-base-container-3-cols-33-33-33',
        '3 Columns 33 % / 33 % / 33 %',
        'Container with max. 3 columns.',
        [
            [
                [
                    'name' => 'Left',
                    'colPos' => 300,
                    'allowed' => [
                        'CType' => $CTypesForSmallerColumns,
                    ],
                ],
                [
                    'name' => 'Middle',
                    'colPos' => 301,
                    'allowed' => [
                        'CType' => $CTypesForSmallerColumns,
                    ],
                ],
                [
                    'name' => 'Right',
                    'colPos' => 302,
                    'allowed' => [
                        'CType' => $CTypesForSmallerColumns,
                    ],
                ],
            ],
        ]
    )
    )
        ->setIcon('EXT:container/Resources/Public/Icons/container-3col.svg')
);
//</editor-fold>
