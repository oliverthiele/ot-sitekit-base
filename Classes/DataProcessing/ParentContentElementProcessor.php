<?php

declare(strict_types=1);

namespace OliverThiele\OtSitekitbase\DataProcessing;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

/**
 * Reads parent content element(s) for a given tt_content element.
 */
class ParentContentElementProcessor implements DataProcessorInterface
{
    /**
     * Process data for getting information about the parent content element
     *
     * @param ContentObjectRenderer $cObj The data of the content element or page
     * @param array<string, mixed> $contentObjectConfiguration The configuration of Content Object
     * @param array<string, mixed> $processorConfiguration The configuration of this processor
     * @param array<string, mixed> $processedData Key/value store of processed data (e.g. to be passed to a Fluid View)
     * @return array<mixed> the processed data as key/value store
     * @throws Exception
     */
    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData,
    ): array {
        if (isset($processorConfiguration['if.']) && !$cObj->checkIf($processorConfiguration['if.'])) {
            return $processedData;
        }

        $record = $cObj->data ?? [];
        if (empty($record['uid'])) {
            return $processedData;
        }

        // Field that stores parent UID (usually from container extension)
        $parentField = $processorConfiguration['parentField'] ?? 'tx_container_parent';
        $parentUid = (int)($record[$parentField] ?? 0);
        if ($parentUid <= 0) {
            return $processedData;
        }

        // Limit recursion depth
        $maxDepth = (int)($processorConfiguration['maxDepth'] ?? 3);

        // Parse desired fields
        $fields = [];
        if (!empty($processorConfiguration['fields'])) {
            $fields = GeneralUtility::trimExplode(',', $processorConfiguration['fields'], true);
        }

        $parents = [];
        $currentParentUid = $parentUid;

        while ($currentParentUid > 0 && $maxDepth-- > 0) {
            $parentRecord = $this->getContentRecord($currentParentUid, $fields);
            if (!$parentRecord) {
                break;
            }

            $parents[] = $parentRecord;
            $currentParentUid = (int)($parentRecord[$parentField] ?? 0);
        }

        $processedData['parentElements'] = $parents;
        $processedData['directParent'] = $parents[0] ?? null;

        return $processedData;
    }

    /**
     * Retrieves a content record from the database table 'tt_content' based on its UID.
     * Optionally allows specifying the fields to select.
     *
     * @param int $uid The unique identifier of the content record to fetch.
     * @param string[] $fields An optional array of field names to select. Defaults to all fields ('*') if empty.
     * @return array|null The fetched content record as an associative array, or null if no record is found.
     * @throws Exception
     */
    protected function getContentRecord(int $uid, array $fields = []): ?array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tt_content');

        $queryBuilder->getRestrictions()->removeAll()
            ->add(
                GeneralUtility::makeInstance(
                    FrontendRestrictionContainer::class
                )
            );

        $selectFields = empty($fields) ? ['*'] : $fields;

        $record = $queryBuilder
            ->select(...$selectFields)
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, ParameterType::INTEGER))
            )
            ->executeQuery()
            ->fetchAssociative();

        return $record ?: null;
    }
}
