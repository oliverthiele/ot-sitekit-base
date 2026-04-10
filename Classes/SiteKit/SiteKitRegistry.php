<?php

declare(strict_types=1);

namespace OliverThiele\OtSitekitbase\SiteKit;

use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Registry that aggregates CType group declarations from all active packages.
 *
 * Each package can define its CTypes and their groups in two ways:
 *  1. Configuration/SiteKit.yaml  — package-level declarations and overrides
 *  2. ContentBlocks/{*}/SiteKit.yaml — per-ContentBlock declarations (no ctype needed)
 *
 * Instantiable via GeneralUtility::makeInstance() (TCA phase) and DI (EventListener).
 */
final class SiteKitRegistry
{
    private static ?array $cache = null;

    /**
     * Returns a map of group names to arrays of CTypes.
     *
     * Processing order:
     *  Pass 1 — collect all elements from Configuration/SiteKit.yaml and ContentBlock SiteKit.yaml files
     *  Pass 2 — apply all overrides (addToGroups / removeFromGroups) from Configuration/SiteKit.yaml
     *
     * @return array<string, list<string>>
     */
    public function getGroupToCTypeMap(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $packageManager = GeneralUtility::makeInstance(PackageManager::class);
        $map = [];
        $overrides = [];

        foreach ($packageManager->getActivePackages() as $package) {
            $packagePath = $package->getPackagePath();

            // Pass 1a: package-level Configuration/SiteKit.yaml
            $siteKitYaml = $packagePath . 'Configuration/SiteKit.yaml';
            if (file_exists($siteKitYaml)) {
                $config = Yaml::parseFile($siteKitYaml);

                if (isset($config['elements']) && is_array($config['elements'])) {
                    foreach ($config['elements'] as $element) {
                        if (!isset($element['ctype'], $element['groups']) || !is_array($element['groups'])) {
                            continue;
                        }
                        $ctype = (string)$element['ctype'];
                        foreach ($element['groups'] as $group) {
                            $map[(string)$group][] = $ctype;
                        }
                    }
                }

                if (isset($config['overrides']) && is_array($config['overrides'])) {
                    $overrides = array_merge($overrides, $config['overrides']);
                }
            }

            // Pass 1b: ContentBlock-level SiteKit.yaml (recursive scan under ContentBlocks/)
            $contentBlocksDir = $packagePath . 'ContentBlocks/';
            if (is_dir($contentBlocksDir)) {
                $this->scanContentBlocks($contentBlocksDir, $map);
            }
        }

        // Pass 2: apply overrides collected from all packages
        foreach ($overrides as $override) {
            if (!isset($override['ctype'])) {
                continue;
            }
            $ctype = (string)$override['ctype'];

            if (isset($override['removeFromGroups']) && is_array($override['removeFromGroups'])) {
                $groups = in_array('*', $override['removeFromGroups'], true)
                    ? array_keys($map)
                    : $override['removeFromGroups'];
                foreach ($groups as $group) {
                    $group = (string)$group;
                    if (isset($map[$group])) {
                        $map[$group] = array_values(
                            array_filter($map[$group], fn(string $ct): bool => $ct !== $ctype)
                        );
                    }
                }
            }

            if (isset($override['addToGroups']) && is_array($override['addToGroups'])) {
                foreach ($override['addToGroups'] as $group) {
                    $group = (string)$group;
                    if (!in_array($ctype, $map[$group] ?? [], true)) {
                        $map[$group][] = $ctype;
                    }
                }
            }
        }

        self::$cache = $map;
        return $map;
    }

    /**
     * Resolves a list of groups and/or direct CTypes to a comma-separated, deduplicated CType list.
     * Entries prefixed with "group_" are resolved via the group map.
     * Entries without "group_" prefix are used directly as CType identifiers.
     *
     * @param list<string> $groupsAndCTypes
     */
    public function getCTypesForGroups(array $groupsAndCTypes): string
    {
        $map = $this->getGroupToCTypeMap();
        $cTypes = [];

        foreach ($groupsAndCTypes as $item) {
            $item = trim($item);
            if (str_starts_with($item, 'group_')) {
                $cTypes = array_merge($cTypes, $map[$item] ?? []);
            } else {
                $cTypes[] = $item;
            }
        }

        return implode(',', array_unique($cTypes));
    }

    /**
     * Merges any number of CType strings (comma-separated lists or single values)
     * into one clean, deduplicated, comma-separated string.
     *
     * Accepts variables returned by getCTypesForGroups() as well as plain strings,
     * comma-separated literals, or a mix of all three:
     *
     *   $registry->mergeCTypes($CTypesWide, $CTypesAdvanced, 'my-ctype, other-ctype')
     */
    public function mergeCTypes(string ...$parts): string
    {
        $cTypes = [];

        foreach ($parts as $part) {
            foreach (explode(',', $part) as $item) {
                $item = trim($item);
                if ($item !== '') {
                    $cTypes[] = $item;
                }
            }
        }

        return implode(', ', array_unique($cTypes));
    }

    /**
     * Recursively scans a ContentBlocks directory for SiteKit.yaml files.
     * Each SiteKit.yaml must be accompanied by a config.yaml in the same directory.
     * The CType is derived from the ContentBlock name: "vendor/element-name" → "vendorelementname" → "vendor_elementname"
     * (hyphens are removed, slash becomes underscore).
     *
     * @param array<string, list<string>> $map
     */
    private function scanContentBlocks(string $contentBlocksDir, array &$map): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($contentBlocksDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getFilename() !== 'SiteKit.yaml') {
                continue;
            }

            $cbDir = $file->getPath() . '/';
            $cbConfigFile = $cbDir . 'config.yaml';
            if (!file_exists($cbConfigFile)) {
                continue;
            }

            $cbConfig = Yaml::parseFile($cbConfigFile);
            if (!isset($cbConfig['name'])) {
                continue;
            }

            // "oliver-thiele/price-card" → "oliverthiele_pricecard"
            $ctype = str_replace('/', '_', str_replace('-', '', strtolower((string)$cbConfig['name'])));

            $cbSiteKit = Yaml::parseFile((string)$file);
            if (!isset($cbSiteKit['groups']) || !is_array($cbSiteKit['groups'])) {
                continue;
            }

            foreach ($cbSiteKit['groups'] as $group) {
                $map[(string)$group][] = $ctype;
            }
        }
    }
}
