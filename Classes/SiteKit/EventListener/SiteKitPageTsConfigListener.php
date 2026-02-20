<?php

declare(strict_types=1);

namespace OliverThiele\OtSitekitbase\SiteKit\EventListener;

use OliverThiele\OtSitekitbase\SiteKit\SiteKitRegistry;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Event\ModifyLoadedPageTsConfigEvent;

/**
 * Resolves __SITEKIT:group_hero,my_ctype__ markers in page TSconfig.
 * Entries prefixed with "group_" are expanded to all CTypes of that group.
 * Other entries are used directly as CType identifiers.
 */
#[AsEventListener(identifier: 'ot_sitekitbase.sitekit.page-tsconfig')]
final class SiteKitPageTsConfigListener
{
    public function __construct(
        private readonly SiteKitRegistry $registry
    ) {
    }

    public function __invoke(ModifyLoadedPageTsConfigEvent $event): void
    {
        $tsConfig = $event->getTsConfig();

        foreach ($tsConfig as $key => $content) {
            $tsConfig[$key] = preg_replace_callback(
                '/__SITEKIT:([a-zA-Z0-9_,\-]+)__/',
                fn(array $matches): string => $this->registry->getCTypesForGroups(
                    array_map('trim', explode(',', $matches[1]))
                ),
                $content
            );
        }

        $event->setTsConfig($tsConfig);
    }
}
