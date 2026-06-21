<?php

declare(strict_types=1);

namespace OliverThiele\OtSitekitbase\Upgrades;

use TYPO3\CMS\Core\Attribute\UpgradeWizard;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Upgrades\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Core\Upgrades\UpgradeWizardInterface;

#[UpgradeWizard('sitekitBgColorContainerMigration')]
final readonly class BgColorContainerMigration implements UpgradeWizardInterface
{
    private const TABLE_NAME = 'tt_content';
    private const OLD_CTYPE = 'ot-sitekit-base-container-1-col-bgcolor';

    private const LAYOUT_TO_CTYPE_MAP = [
        '' => 'ot-sitekit-base-container-bgcolor-primary',
        'secondary' => 'ot-sitekit-base-container-bgcolor-secondary',
        'light' => 'ot-sitekit-base-container-bgcolor-light',
        'dark' => 'ot-sitekit-base-container-bgcolor-dark',
    ];

    public function __construct(
        private ConnectionPool $connectionPool,
    ) {}

    public function getTitle(): string
    {
        return 'Migrate background color containers to separate CTypes';
    }

    public function getDescription(): string
    {
        $count = $this->getAffectedRecordCount();
        return sprintf(
            'Migrates %d "Container with background-color" record(s) from the single CType '
            . 'with ot_layout selector to four separate CTypes (primary, secondary, light, dark).',
            $count
        );
    }

    public function updateNecessary(): bool
    {
        return $this->getAffectedRecordCount() > 0;
    }

    public function executeUpdate(): bool
    {
        $connection = $this->connectionPool->getConnectionForTable(self::TABLE_NAME);

        foreach (self::LAYOUT_TO_CTYPE_MAP as $layoutValue => $newCType) {
            $connection->update(
                self::TABLE_NAME,
                ['CType' => $newCType],
                ['CType' => self::OLD_CTYPE, 'ot_layout' => $layoutValue],
            );
        }

        return true;
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    private function getAffectedRecordCount(): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder->getRestrictions()->removeAll();

        return (int)$queryBuilder
            ->count('uid')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq(
                    'CType',
                    $queryBuilder->createNamedParameter(self::OLD_CTYPE)
                )
            )
            ->executeQuery()
            ->fetchOne();
    }
}