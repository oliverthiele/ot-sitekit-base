<?php

$EM_CONF['ot_sitekitbase'] = [
    'title' => 'SiteKit Base',
    'description' => 'Foundation of the SiteKit system: Bootstrap 5 page templates, content element and container layouts, backend layouts and shared site settings.',
    'category' => 'fe',
    'author' => 'Oliver Thiele',
    'author_email' => 'mail@oliver-thiele.de',
    'author_company' => 'Web Development Oliver Thiele',
    'state' => 'stable',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '14.3.0-14.99.99',
            'php' => '8.4.0-8.99.99',
            'ot_icons' => '3.0.0-3.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
