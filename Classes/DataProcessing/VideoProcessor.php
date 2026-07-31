<?php

declare(strict_types=1);

/**
 * Copyright notice
 *
 * (c) 2025 Oliver Thiele <mail@oliver-thiele.de>, Web Development Oliver Thiele
 * All rights reserved
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * This copyright notice MUST APPEAR in all copies of the script!
 */

namespace OliverThiele\OtSitekitbase\DataProcessing;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Resource\AbstractFile;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\FileType;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

/**
 * Processes a FAL folder into a normalized video data structure for the Video Atom.
 *
 * Folder convention:
 *   /fileadmin/videos/my-video/
 *   ├── video_1080p.mp4      resolution suffix: _360p _480p _720p _1080p _1440p _2160p _4k
 *   ├── video_1080p.webm     WebM is served before MP4 per resolution level
 *   ├── poster.jpg           or .webp / .png — filename "poster" has priority
 *   ├── captions.de.vtt      track pattern: {kind}.{lang}.vtt
 *   ├── chapters.en.vtt
 *   ├── transcript.de.txt    transcript pattern: transcript.{lang}.txt
 *   └── meta.yaml            optional: title, description, uploadDate, duration, schemaType,
 *                                      aspectRatio (e.g. "16:9", "1:1", "4:3", "21:9"),
 *                                      loop, autoplay, decorative, controls
 *
 * TypoScript usage:
 *   dataProcessing.10 = OliverThiele\OtSitekitbase\DataProcessing\VideoProcessor
 *   dataProcessing.10 {
 *       as = video
 *       folderPath = 1:/videos/my-video/
 *       # or: read folder path from a content element field
 *       # folderField = tx_myextension_video_folder
 *       # or: derive folder from an already-processed files array (e.g. FilesProcessor output)
 *       # filesVariable = files
 *   }
 */
class VideoProcessor implements DataProcessorInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** Resolutions checked in descending order — highest quality first in <source> list */
    private const RESOLUTION_ORDER = ['2160p', '4k', '1440p', '1080p', '720p', '480p', '360p'];

    /**
     * Minimum element width (in px) at which each resolution becomes beneficial.
     * Used by VideoSourcesViewHelper to calculate responsive media queries.
     * Rule of thumb: resolution name ≈ vertical pixels; horizontal threshold = name value
     * (e.g. 720p is 1280×720 — useful when element ≥ 720 px wide).
     */
    private const RESOLUTION_ELEMENT_THRESHOLDS = [
        '2160p' => 2160,
        '4k'    => 2160,
        '1440p' => 1440,
        '1080p' => 1080,
        '720p'  => 720,
        '480p'  => 480,
        '360p'  => 360,
    ];

    /** MIME types for supported video file extensions */
    private const MIME_TYPES = [
        'webm' => 'video/webm',
        'mp4'  => 'video/mp4',
        'ogg'  => 'video/ogg',
        'mov'  => 'video/quicktime',
    ];

    private const VIDEO_EXTENSIONS = ['mp4', 'webm', 'ogg', 'mov'];

    private const IMAGE_EXTENSIONS = ['webp', 'jpg', 'jpeg', 'png', 'gif'];

    private const TRACK_KINDS = ['captions', 'subtitles', 'chapters', 'descriptions'];

    /** Format order within each resolution group — WebM before MP4 for better compression */
    private const SOURCE_FORMAT_PRIORITY = ['webm', 'mp4', 'ogg', 'mov'];

    private const LANGUAGE_LABELS = [
        'de' => 'Deutsch',
        'en' => 'English',
        'fr' => 'Français',
        'es' => 'Español',
        'it' => 'Italiano',
        'nl' => 'Nederlands',
        'pl' => 'Polski',
        'pt' => 'Português',
    ];

    public function __construct(
        private readonly ResourceFactory $resourceFactory,
    ) {
    }

    /**
     * @param ContentObjectRenderer $cObj
     * @param array<string, mixed> $contentObjectConfiguration
     * @param array<string, mixed> $processorConfiguration
     * @param array<string, mixed> $processedData
     * @return array<mixed>
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

        $outputKey = $processorConfiguration['as'] ?? 'video';
        $contentData = $cObj->data;

        // Mode A: derive folder from an already-processed files array (e.g. FilesProcessor output)
        // Scans the parent folder of the first video file found in the array.
        // Silent no-op when no video file is present — safe to add to lib.contentElement globally.
        if (!empty($processorConfiguration['filesVariable'])) {
            $filesVariableName = (string)$processorConfiguration['filesVariable'];
            $processedFiles = $processedData[$filesVariableName] ?? [];

            // Early exit: skip the full folder scan when the array contains no video file at all.
            // getType() reads from already-loaded FAL properties — no extra DB query.
            if (!$this->filesArrayContainsVideo($processedFiles)) {
                return $processedData;
            }

            $folder = $this->resolveFolderFromFilesVariable($processedFiles);
            if ($folder === null) {
                return $processedData;
            }
            $videoData = $this->processFolder($folder, $contentData);
            // FAL FileReference title/description override meta.yaml when explicitly set.
            // NULL = checkbox not checked in backend → inherit, do not override meta.yaml.
            // '' or non-empty = checkbox checked (even if left empty) → override meta.yaml.
            $falOverrides = $this->resolveFalOverrides($processedFiles);
            if ($falOverrides['title'] !== null) {
                $videoData['title'] = trim((string)$falOverrides['title']);
            }
            if ($falOverrides['description'] !== null) {
                $videoData['description'] = trim((string)$falOverrides['description']);
            }
            $processedData[$outputKey] = $videoData;
            return $processedData;
        }

        // Mode B: explicit folder path or content element field containing the folder path
        $folderIdentifier = '';
        if (!empty($processorConfiguration['folderPath'])) {
            $folderIdentifier = (string)$cObj->stdWrapValue('folderPath', $processorConfiguration);
        } elseif (!empty($processorConfiguration['folderField'])) {
            $fieldName = (string)$processorConfiguration['folderField'];
            $folderIdentifier = trim((string)($contentData[$fieldName] ?? ''));
        }

        if ($folderIdentifier === '') {
            $this->logger?->warning('VideoProcessor: No input configured (folderPath, folderField, or filesVariable)', [
                'uid' => $contentData['uid'] ?? 0,
                'pid' => $contentData['pid'] ?? 0,
            ]);
            $processedData[$outputKey] = $this->buildErrorState((int)($contentData['uid'] ?? 0));
            return $processedData;
        }

        try {
            $folder = $this->resourceFactory->getFolderObjectFromCombinedIdentifier($folderIdentifier);
        } catch (\Exception $exception) {
            $this->logger?->warning('VideoProcessor: Folder not found', [
                'folder' => $folderIdentifier,
                'uid'    => $contentData['uid'] ?? 0,
                'pid'    => $contentData['pid'] ?? 0,
                'error'  => $exception->getMessage(),
            ]);
            $processedData[$outputKey] = $this->buildErrorState((int)($contentData['uid'] ?? 0));
            return $processedData;
        }

        $processedData[$outputKey] = $this->processFolder($folder, $contentData);
        return $processedData;
    }

    /**
     * Returns true if the array contains at least one video file.
     * Used as a cheap pre-check before the full folder scan.
     *
     * @param mixed $processedFiles
     */
    private function filesArrayContainsVideo(mixed $processedFiles): bool
    {
        if (!is_array($processedFiles)) {
            return false;
        }
        foreach ($processedFiles as $processedFile) {
            if (!($processedFile instanceof FileInterface)) {
                continue;
            }
            $fileObject = $processedFile instanceof FileReference
                ? $processedFile->getOriginalFile()
                : $processedFile;
            if ($fileObject instanceof AbstractFile
                && $fileObject->getType() === FileType::VIDEO->value
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Finds the first video file in a processed files array and returns its parent folder.
     * Used when VideoProcessor reads from a FilesProcessor output variable.
     * Returns null when no video file is present — caller skips silently.
     *
     * @param mixed $processedFiles
     */
    private function resolveFolderFromFilesVariable(mixed $processedFiles): ?Folder
    {
        if (!is_array($processedFiles)) {
            return null;
        }

        foreach ($processedFiles as $processedFile) {
            if (!($processedFile instanceof FileInterface)) {
                continue;
            }

            // Resolve the underlying File object (FileReference wraps it)
            $fileObject = $processedFile instanceof FileReference
                ? $processedFile->getOriginalFile()
                : $processedFile;

            if (!($fileObject instanceof AbstractFile)) {
                continue;
            }

            if ($fileObject->getType() !== FileType::VIDEO->value) {
                continue;
            }

            try {
                return $fileObject->getParentFolder();
            } catch (\Exception $exception) {
                $this->logger?->warning('VideoProcessor: Could not resolve parent folder of video file', [
                    'file'  => $fileObject->getIdentifier(),
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return null;
    }

    /**
     * Extracts title and description from the first video FileReference in the processed files array.
     *
     * Uses getProperties() which returns the raw array_merge of file + reference properties,
     * preserving NULL values from sys_file_reference. This allows distinguishing:
     *   NULL  = override checkbox not checked in backend → do not override meta.yaml
     *   ''    = override checkbox checked, intentionally left empty → override meta.yaml
     *   'Foo' = override checkbox checked with a value → override meta.yaml
     *
     * @param mixed $processedFiles
     * @return array{title: string|null, description: string|null}
     */
    private function resolveFalOverrides(mixed $processedFiles): array
    {
        $result = ['title' => null, 'description' => null];

        if (!is_array($processedFiles)) {
            return $result;
        }

        foreach ($processedFiles as $processedFile) {
            if (!($processedFile instanceof FileReference)) {
                continue;
            }

            $fileObject = $processedFile->getOriginalFile();
            if ($fileObject->getType() !== FileType::VIDEO->value) {
                continue;
            }

            // getProperties() = array_merge(file properties, reference properties).
            // Reference properties retain their raw DB values including NULL.
            $properties = $processedFile->getProperties();
            $result['title']       = array_key_exists('title', $properties) ? $properties['title'] : null;
            $result['description'] = array_key_exists('description', $properties) ? $properties['description'] : null;
            break;
        }

        return $result;
    }

    /**
     * Scans the folder and returns the normalized video data array.
     *
     * @param array<string, mixed> $contentData
     * @return array<string, mixed>
     */
    private function processFolder(Folder $folder, array $contentData): array
    {
        $files = $folder->getFiles();
        $uid = (int)($contentData['uid'] ?? 0);

        $videoFiles = [];
        $posterFile = null;
        $posterFileFallback = null;
        $tracks = [];
        $transcripts = [];
        $metaData = [];
        $isLoop = false;

        foreach ($files as $file) {
            $extension = strtolower($file->getExtension());
            $baseNameLower = strtolower($file->getNameWithoutExtension());
            $fileName = $file->getName();

            if (in_array($extension, self::VIDEO_EXTENSIONS, true)) {
                // Detect loop flag via filename keyword _loop (e.g. hero_loop_1080p.mp4)
                if (str_contains($baseNameLower, '_loop')) {
                    $isLoop = true;
                }
                $videoFiles[] = $file;
            } elseif (in_array($extension, self::IMAGE_EXTENSIONS, true)) {
                // A file named exactly "poster" takes priority over any other image
                if ($baseNameLower === 'poster') {
                    $posterFile = $file;
                } elseif ($posterFileFallback === null) {
                    $posterFileFallback = $file;
                }
            } elseif ($extension === 'vtt') {
                $track = $this->parseTrackFile($fileName, $file);
                if ($track !== null) {
                    $tracks[] = $track;
                }
            } elseif (in_array($extension, ['txt', 'md'], true)) {
                $language = $this->parseLanguageCode($baseNameLower);
                if ($language !== null) {
                    $transcripts[$language] = $file->getContents();
                }
            } elseif ($fileName === 'meta.yaml') {
                $metaData = $this->parseMetaYaml($file);
            }
        }

        $resolvedPoster = $posterFile ?? $posterFileFallback;
        $hasSources = !empty($videoFiles);
        $hasPoster  = $resolvedPoster !== null;

        if (!$hasSources && !$hasPoster) {
            $this->logger?->warning('VideoProcessor: Folder contains neither video files nor a poster image', [
                'folder' => $folder->getIdentifier(),
                'uid'    => $uid,
            ]);
            return $this->buildErrorState($uid);
        }

        $state = match (true) {
            !$hasSources => 'videoMissing',
            !$hasPoster  => 'posterMissing',
            default      => 'ok',
        };

        if ($state !== 'ok') {
            $this->logger?->info('VideoProcessor: Incomplete video folder', [
                'state'  => $state,
                'folder' => $folder->getIdentifier(),
                'uid'    => $uid,
            ]);
        }

        $sources = $this->buildSources($videoFiles);

        // Mark the first captions track as the default track
        foreach ($tracks as $index => $track) {
            if ($track['kind'] === 'captions') {
                $tracks[$index]['default'] = true;
                break;
            }
        }

        // raw: block is written by sitekit:video:process CLI — auto-detected technical facts.
        // Editorial top-level keys take precedence; raw values are the fallback.
        $rawData     = is_array($metaData['raw'] ?? null) ? (array)$metaData['raw'] : [];
        $rawHasAudio = array_key_exists('hasAudio', $rawData) ? (bool)$rawData['hasAudio'] : true;

        $title       = (string)($metaData['title'] ?? '');
        $description = (string)($metaData['description'] ?? '');
        $uploadDate  = (string)($metaData['uploadDate'] ?? '');
        // Editorial duration overrides raw; raw is the ffprobe-detected ISO 8601 value
        $duration    = (string)($metaData['duration'] ?? $rawData['duration'] ?? '');
        $schemaType  = (string)($metaData['schemaType'] ?? 'VideoObject');
        // Editorial aspectRatio overrides raw (e.g. for letterboxed content)
        $aspectRatio = $this->normalizeAspectRatio(
            (string)($metaData['aspectRatio'] ?? $rawData['aspectRatio'] ?? '')
        );
        // meta.yaml loop:true overrides filename detection
        $isLoop      = $isLoop || (bool)($metaData['loop'] ?? false);
        // Display behaviour defined at video-set level
        $isAutoplay  = (bool)($metaData['autoplay'] ?? false);
        $isDecorative = (bool)($metaData['decorative'] ?? false);
        $showControls = (bool)($metaData['controls'] ?? true);
        $license     = (string)($metaData['license'] ?? '');
        $licenseUrl  = (string)($metaData['licenseUrl'] ?? '');

        // JSON-LD requires at minimum: name, description, thumbnailUrl, uploadDate
        $hasStructuredData = $title !== '' && $description !== '' && $uploadDate !== '' && $hasPoster;
        $structuredDataJson = '';

        if ($hasStructuredData && $resolvedPoster !== null) {
            $structuredDataJson = $this->buildStructuredDataJson(
                $schemaType,
                $title,
                $description,
                $uploadDate,
                $duration,
                $licenseUrl,
                $resolvedPoster,
                $sources,
                $transcripts,
            );
        }

        // Best source = highest available resolution (sources are already sorted descending).
        // Used by the expand dialog to always load the best quality, regardless of column context.
        $bestSrc     = !empty($sources) ? $sources[0]['url'] : '';
        $bestSrcType = !empty($sources) ? $sources[0]['type'] : '';

        return [
            'uid'                => $uid,
            'state'              => $state,
            'title'              => $title,
            'description'        => $description,
            'uploadDate'         => $uploadDate,
            'duration'           => $duration,
            'schemaType'         => $schemaType,
            'aspectRatio'        => $aspectRatio,
            'hasAudio'           => $rawHasAudio,
            'bestSrc'            => $bestSrc,
            'bestSrcType'        => $bestSrcType,
            'autoplay'           => $isAutoplay,
            'loop'               => $isLoop,
            'decorative'         => $isDecorative,
            'controls'           => $showControls,
            'poster'             => $resolvedPoster,
            'sources'            => $sources,
            'tracks'             => $tracks,
            'transcripts'        => $transcripts,
            'license'            => $license,
            'licenseUrl'         => $licenseUrl,
            'hasStructuredData'  => $hasStructuredData,
            'structuredDataJson' => $structuredDataJson,
        ];
    }

    /**
     * Groups video files by resolution, sorts descending (highest first),
     * with WebM before MP4 within each resolution group.
     *
     * @param FileInterface[] $videoFiles
     * @return array<int, array{url: string, type: string, resolutionWidth: int|null}>
     */
    private function buildSources(array $videoFiles): array
    {
        $grouped = [];
        $ungrouped = [];

        foreach ($videoFiles as $file) {
            $extension = strtolower($file->getExtension());
            if (!array_key_exists($extension, self::MIME_TYPES)) {
                continue;
            }

            $resolution = $this->detectResolution($file->getNameWithoutExtension());
            if ($resolution !== null) {
                $grouped[$resolution][$extension] = $file;
            } else {
                // No resolution suffix — used as lowest-priority fallback
                $ungrouped[$extension] = $file;
            }
        }

        $sources = [];

        foreach (self::RESOLUTION_ORDER as $resolution) {
            if (!isset($grouped[$resolution])) {
                continue;
            }
            // resolutionWidth: minimum element width (px) at which this resolution is beneficial.
            // Passed to VideoSourcesViewHelper for responsive media query calculation.
            $resolutionWidth = self::RESOLUTION_ELEMENT_THRESHOLDS[$resolution] ?? null;
            foreach (self::SOURCE_FORMAT_PRIORITY as $extension) {
                if (isset($grouped[$resolution][$extension])) {
                    $sources[] = [
                        'url'            => (string)$grouped[$resolution][$extension]->getPublicUrl(),
                        'type'           => self::MIME_TYPES[$extension],
                        'resolutionWidth' => $resolutionWidth,
                    ];
                }
            }
        }

        // Append files without a resolution suffix (WebM first) — resolutionWidth unknown
        foreach (self::SOURCE_FORMAT_PRIORITY as $extension) {
            if (isset($ungrouped[$extension])) {
                $sources[] = [
                    'url'            => (string)$ungrouped[$extension]->getPublicUrl(),
                    'type'           => self::MIME_TYPES[$extension],
                    'resolutionWidth' => null,
                ];
            }
        }

        return $sources;
    }

    /**
     * Extracts the resolution label from a filename without extension.
     * Matches _720p at the end OR followed by a non-alphanumeric character (e.g. _720p-3mb, _1080p_hdr).
     * Example: "hero_1080p" → "1080p", "video_720p-3mb" → "720p"
     */
    private function detectResolution(string $nameWithoutExtension): ?string
    {
        $lowerName = strtolower($nameWithoutExtension);
        foreach (self::RESOLUTION_ORDER as $resolution) {
            if (preg_match('/_' . preg_quote($resolution, '/') . '([^a-z0-9]|$)/', $lowerName)) {
                return $resolution;
            }
        }
        return null;
    }

    /**
     * Parses a VTT filename into a track descriptor.
     * Expected pattern: {kind}.{lang}.vtt — e.g. captions.de.vtt, chapters.en.vtt
     *
     * @return array{kind: string, url: string, srclang: string, label: string, default: bool}|null
     */
    private function parseTrackFile(string $fileName, FileInterface $file): ?array
    {
        $parts = explode('.', pathinfo($fileName, PATHINFO_FILENAME));
        if (count($parts) !== 2) {
            return null;
        }

        [$kind, $language] = $parts;
        $kind     = strtolower($kind);
        $language = strtolower($language);

        if (!in_array($kind, self::TRACK_KINDS, true)) {
            return null;
        }

        return [
            'kind'    => $kind,
            'url'     => (string)$file->getPublicUrl(),
            'srclang' => $language,
            'label'   => self::LANGUAGE_LABELS[$language] ?? strtoupper($language),
            'default' => false,
        ];
    }

    /**
     * Extracts a two-letter ISO 639-1 language code from a filename base.
     * Example: "transcript.de" → "de"
     */
    private function parseLanguageCode(string $nameWithoutExtension): ?string
    {
        $parts = explode('.', $nameWithoutExtension);
        $lastPart = strtolower(end($parts));
        if (strlen($lastPart) === 2 && ctype_alpha($lastPart)) {
            return $lastPart;
        }
        return null;
    }

    /**
     * Parses the meta.yaml file and returns its data as an array.
     *
     * @return array<string, mixed>
     */
    private function parseMetaYaml(FileInterface $file): array
    {
        try {
            $content = $file->getContents();
            $data = Yaml::parse($content);
            return is_array($data) ? $data : [];
        } catch (ParseException $exception) {
            $this->logger?->warning('VideoProcessor: Failed to parse meta.yaml', [
                'file'  => $file->getIdentifier(),
                'error' => $exception->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Builds a JSON-LD VideoObject (or subtype) string for schema.org structured data.
     * Only called when all required fields (name, description, thumbnailUrl, uploadDate) are present.
     *
     * @param array<int, array{url: string, type: string, resolutionWidth: int|null}> $sources
     * @param array<string, string> $transcripts
     */
    private function buildStructuredDataJson(
        string $schemaType,
        string $title,
        string $description,
        string $uploadDate,
        string $duration,
        string $licenseUrl,
        FileInterface $posterFile,
        array $sources,
        array $transcripts,
    ): string {
        $siteUrl = rtrim((string)GeneralUtility::getIndpEnv('TYPO3_SITE_URL'), '/');

        $data = [
            '@context'     => 'https://schema.org',
            '@type'        => $schemaType,
            'name'         => $title,
            'description'  => $description,
            'thumbnailUrl' => $siteUrl . $posterFile->getPublicUrl(),
            'uploadDate'   => $uploadDate,
        ];

        if ($duration !== '') {
            $data['duration'] = $duration;
        }

        if ($licenseUrl !== '') {
            $data['license'] = $licenseUrl;
        }

        // contentUrl points to the highest-quality source (first entry after sorting)
        if (!empty($sources)) {
            $data['contentUrl'] = $siteUrl . $sources[0]['url'];
        }

        // Use the first available transcript language
        if (!empty($transcripts)) {
            $data['transcript'] = reset($transcripts);
        }

        return (string)json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    /**
     * Converts an aspect ratio string to Bootstrap ratio class suffix format.
     * Accepts both colon notation ("16:9") and x-notation ("16x9").
     * Returns empty string for unknown/unsupported ratios.
     */
    private function normalizeAspectRatio(string $aspectRatio): string
    {
        $normalized = str_replace(':', 'x', trim($aspectRatio));
        $allowed = ['1x1', '4x3', '16x9', '21x9'];
        return in_array($normalized, $allowed, true) ? $normalized : '';
    }

    /**
     * Returns a normalized error state — used when the folder is missing or empty.
     *
     * @return array<string, mixed>
     */
    private function buildErrorState(int $uid): array
    {
        return [
            'uid'                => $uid,
            'state'              => 'error',
            'title'              => '',
            'description'        => '',
            'uploadDate'         => '',
            'duration'           => '',
            'schemaType'         => 'VideoObject',
            'aspectRatio'        => '',
            'hasAudio'           => true,
            'bestSrc'            => '',
            'bestSrcType'        => '',
            'autoplay'           => false,
            'loop'               => false,
            'decorative'         => false,
            'controls'           => true,
            'poster'             => null,
            'sources'            => [],
            'tracks'             => [],
            'transcripts'        => [],
            'license'            => '',
            'licenseUrl'         => '',
            'hasStructuredData'  => false,
            'structuredDataJson' => '',
        ];
    }
}
