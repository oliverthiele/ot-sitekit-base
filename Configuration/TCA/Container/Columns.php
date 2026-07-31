<?php

declare(strict_types=1);

use B13\Container\Tca\ContainerConfiguration;
use B13\Container\Tca\Registry;
use OliverThiele\OtSitekitbase\SiteKit\SiteKitRegistry;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;

$ll = 'ot_sitekitbase.db:';

$siteKitRegistry = GeneralUtility::makeInstance(SiteKitRegistry::class);
$CTypesForSmallerColumns = $siteKitRegistry->getCTypesForGroups(['group_content_small']);
$CTypesFullContainerWidth = $siteKitRegistry->getCTypesForGroups(['group_content_wide']);

// b13/container v4+ (TYPO3 v14) uses allowedContentTypes/disallowedContentTypes (string).
// b13/container v3 (TYPO3 v13) uses allowed.CType/disallowed.CType (array).
$typo3MajorVersion = (new Typo3Version())->getMajorVersion();
if ($typo3MajorVersion >= 14) {
    $restrictionWide = ['allowedContentTypes' => $CTypesFullContainerWidth];
    $restrictionSmall = ['allowedContentTypes' => $CTypesForSmallerColumns];
} else {
    $restrictionWide = ['allowed' => ['CType' => $CTypesFullContainerWidth], 'disallowed' => ['CType' => '']];
    $restrictionSmall = ['allowed' => ['CType' => $CTypesForSmallerColumns]];
}

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
                    'colspan' => 1,
                    ...$restrictionWide,
                ],
            ],
        ]
    )
    )
        ->setIcon('EXT:container/Resources/Public/Icons/container-1col.svg')
        ->setGroup('pageContainer')
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
                    'colspan' => 1,
                    ...$restrictionWide,
                ],
            ],
        ]
    )
    )
        ->setIcon('EXT:container/Resources/Public/Icons/container-1col.svg')
        ->setGroup('pageContainer')
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
                    'colspan' => 1,
                    ...$restrictionWide,
                ],
            ],
        ]
    )
    )
        ->setIcon('EXT:container/Resources/Public/Icons/container-1col.svg')
    //        ->setGroup('containerSpecial')
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
                    'colspan' => 1,
                    ...$restrictionWide,
                ],
            ],
        ]
    )
    )
        ->setIcon('EXT:container/Resources/Public/Icons/container-1col.svg')
    //        ->setGroup('containerSpecial')
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
        $ll . 'container.cols.2.6-6',
        '',
        [
            [
                [
                    'name' => $ll . 'container.labels.leftColumn',
                    'colPos' => 200,
                    'colspan' => 1,
                    ...$restrictionSmall,
                ],
                [
                    'name' => $ll . 'container.labels.rightColumn',
                    'colPos' => 201,
                    'colspan' => 1,
                    ...$restrictionSmall,
                ],
            ],
        ]
    )
    )
        ->setIcon('EXT:container/Resources/Public/Icons/container-2col.svg')
        ->setGroup('container2Cols')
);
//</editor-fold>

//<editor-fold desc="Container Extension Configuration: 2 columns 33 % / 66 %>
GeneralUtility::makeInstance(Registry::class)->configureContainer(
    (
    new ContainerConfiguration(
        'ot-sitekit-base-container-2-cols-33-66',
        $ll . 'container.cols.2.4-8',
        '',
        [
            [
                [
                    'name' => $ll . 'container.labels.leftColumn',
                    'colPos' => 200,
                    'colspan' => 1,
                    ...$restrictionSmall,
                ],
                [
                    'name' => $ll . 'container.labels.rightColumn',
                    'colPos' => 201,
                    'colspan' => 2,
                    ...$restrictionSmall,
                ],
            ],
        ]
    )
    )
        ->setIcon('EXT:container/Resources/Public/Icons/container-2col-right.svg')
        ->setGroup('container2Cols')
);
//</editor-fold>

//<editor-fold desc="Container Extension Configuration: 2 columns 66 % / 33 %>
GeneralUtility::makeInstance(Registry::class)->configureContainer(
    (
    new ContainerConfiguration(
        'ot-sitekit-base-container-2-cols-66-33',
        $ll . 'container.cols.2.8-4',
        '',
        [
            [
                [
                    'name' => $ll . 'container.labels.leftColumn',
                    'colPos' => 200,
                    'colspan' => 2,
                    ...$restrictionSmall,
                ],
                [
                    'name' => $ll . 'container.labels.rightColumn',
                    'colPos' => 201,
                    'colspan' => 1,
                    ...$restrictionSmall,
                ],
            ],
        ]
    )
    )
        ->setIcon('EXT:container/Resources/Public/Icons/container-2col-left.svg')
        ->setGroup('container2Cols')
);
//</editor-fold>

/**
 * 3 Columns 3 x 33 %
 */
//<editor-fold desc="Container Extension Configuration: 3 columns">
GeneralUtility::makeInstance(Registry::class)->configureContainer(
    (
    new ContainerConfiguration(
        'ot-sitekit-base-container-3-cols-33-33-33',
        $ll . 'container.cols.3.4-4-4',
        $ll . 'container.cols.3.4-4-4.description',
        [
            [
                [
                    'name' => $ll . 'container.labels.leftColumn',
                    'colPos' => 300,
                    'colspan' => 1,
                    ...$restrictionSmall,
                ],
                [
                    'name' => $ll . 'container.labels.middleColumn',
                    'colPos' => 301,
                    'colspan' => 1,
                    ...$restrictionSmall,
                ],
                [
                    'name' => $ll . 'container.labels.rightColumn',
                    'colPos' => 302,
                    'colspan' => 1,
                    ...$restrictionSmall,
                ],
            ],
        ]
    )
    )
        ->setIcon('EXT:container/Resources/Public/Icons/container-3col.svg')
        ->setGroup('container3Cols')
);
//</editor-fold>

/**
 * 3 Columns 25 %, 50 %, 25 %
 */
//<editor-fold desc="Container Extension Configuration: 3 columns">
GeneralUtility::makeInstance(Registry::class)->configureContainer(
    (
    new ContainerConfiguration(
        'ot-sitekit-base-container-3-cols-25-50-25',
        $ll . 'container.cols.3.3-6-3',
        $ll . 'container.cols.3.3-6-3.description',
        [
            [
                [
                    'name' => $ll . 'container.labels.leftColumn',
                    'colPos' => 300,
                    'colspan' => 1,
                    ...$restrictionSmall,
                ],
                [
                    'name' => $ll . 'container.labels.middleColumn',
                    'colPos' => 301,
                    'colspan' => 2,
                    ...$restrictionSmall,
                ],
                [
                    'name' => $ll . 'container.labels.rightColumn',
                    'colPos' => 302,
                    'colspan' => 1,
                    ...$restrictionSmall,
                ],
            ],
        ]
    )
    )
        ->setIcon('EXT:container/Resources/Public/Icons/container-3col.svg')
        ->setGroup('container3Cols')
);
//</editor-fold>

/**
 * 4 Columns 4 x 25%
 */
//<editor-fold desc="Container Extension Configuration: 4 columns">
GeneralUtility::makeInstance(Registry::class)->configureContainer(
    (
    new ContainerConfiguration(
        'ot-sitekit-base-container-4-cols-25-25-25-25',
        $ll . 'container.cols.4.3-3-3-3',
        $ll . 'container.cols.4.3-3-3-3.description',
        [
            [
                [
                    'name' => $ll . 'container.labels.column1',
                    'colPos' => 400,
                    'colspan' => 1,
                    ...$restrictionSmall,
                ],
                [
                    'name' => $ll . 'container.labels.column2',
                    'colPos' => 401,
                    'colspan' => 1,
                    ...$restrictionSmall,
                ],
                [
                    'name' => $ll . 'container.labels.column3',
                    'colPos' => 402,
                    'colspan' => 1,
                    ...$restrictionSmall,
                ],
                [
                    'name' => $ll . 'container.labels.column4',
                    'colPos' => 403,
                    'colspan' => 1,
                    ...$restrictionSmall,
                ],
            ],
        ]
    )
    )
        ->setIcon('EXT:container/Resources/Public/Icons/container-4col.svg')
        ->setGroup('container4Cols')
);
//</editor-fold>
