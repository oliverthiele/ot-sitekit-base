<?php

use OliverThiele\OtSitekitbase\Components\ComponentCollection;

defined('TYPO3') or die();

$GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['sk'][] = 'OliverThiele\OtSitekitbase\ViewHelpers';

$GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['skc'] = [
    ComponentCollection::class,
];
