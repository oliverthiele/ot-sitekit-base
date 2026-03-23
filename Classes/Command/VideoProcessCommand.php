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

namespace OliverThiele\OtSitekitbase\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Scans a configured root folder for video-set subfolders and generates
 * missing poster images and resolution variants using ffmpeg.
 *
 * Usage:
 *   ddev exec typo3 sitekit:video:process
 *   ddev exec typo3 sitekit:video:process --config config/VideoProcessing.yaml
 *   ddev exec typo3 sitekit:video:process --dry-run
 *
 * See: public/fileadmin/Dokumentation/de/CLI_Video_Process.md
 */
#[AsCommand(
    name: 'sitekit:video:process',
    description: 'Scans a video folder and generates missing poster images and resolution variants.'
)]
class VideoProcessCommand extends Command
{
    private const VIDEO_EXTENSIONS = ['mp4', 'webm', 'mov', 'ogg'];

    private const RESOLUTION_SUFFIXES = ['2160p', '4k', '1440p', '1080p', '720p', '480p', '360p'];

    private const META_YAML_SKELETON = <<<'YAML'
# title: "Titel des Videos"
# description: "Kurze Beschreibung (für schema.org JSON-LD und aria-label)"
# uploadDate: "2026-01-01"    # ISO 8601 Datum
# duration: "PT0M30S"         # ISO 8601 Dauer, z.B. PT1M30S = 1 min 30 sec
# schemaType: "VideoObject"   # VideoObject | Clip | BroadcastEvent
# aspectRatio: "16:9"         # 1:1 | 4:3 | 16:9 | 21:9
# loop: false
# autoplay: false
# decorative: false           # true = kein Ton, kein aria-label, keine strukturierten Daten
# controls: true
YAML;

    private SymfonyStyle $io;

    /** @var array<string, mixed> */
    private array $configuration = [];

    private string $ffmpegBinary = '';
    private string $ffprobeBinary = '';
    private bool $isDryRun = false;
    private bool $isForce = false;
    private bool $isForceDisk = false;

    protected function configure(): void
    {
        $this
            ->addOption(
                'config',
                null,
                InputOption::VALUE_OPTIONAL,
                'Path to a project-specific VideoProcessing.yaml (deep-merged with extension defaults).'
            )
            ->addOption(
                'folder',
                null,
                InputOption::VALUE_OPTIONAL,
                'Process only this folder (overrides rootFolder from config).'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Show planned actions without writing any files. Works without ffmpeg.'
            )
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Regenerate files that already exist.'
            )
            ->addOption(
                'force-disk',
                null,
                InputOption::VALUE_NONE,
                'Skip the disk space check.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->isDryRun = (bool)$input->getOption('dry-run');
        $this->isForce = (bool)$input->getOption('force');
        $this->isForceDisk = (bool)$input->getOption('force-disk');

        $this->io->title(
            $this->isDryRun
                ? '[DRY-RUN] sitekit:video:process'
                : 'sitekit:video:process'
        );

        // Load and merge configuration
        $configPath = (string)($input->getOption('config') ?? '');
        if (!$this->loadConfiguration($configPath)) {
            return Command::FAILURE;
        }

        // --folder overrides rootFolder from config
        $folderOption = trim((string)($input->getOption('folder') ?? ''));
        if ($folderOption !== '') {
            $this->configuration['rootFolder'] = $folderOption;
        }

        // Pre-flight checks
        if (!$this->runPreflightChecks()) {
            return Command::FAILURE;
        }

        $rootFolder = $this->resolveRootFolder((string)($this->configuration['rootFolder'] ?? ''));
        if ($rootFolder === null) {
            // Already reported in pre-flight
            return Command::FAILURE;
        }

        // Determine video sets to process
        $videoSets = $this->resolveVideoSets($rootFolder);
        if (empty($videoSets)) {
            $this->io->warning('Keine Video-Sets gefunden in: ' . $rootFolder);
            return Command::SUCCESS;
        }

        // Disk space check before touching anything
        if (!$this->isDryRun && !$this->isForceDisk) {
            if (!$this->checkDiskSpace($videoSets, $rootFolder)) {
                return Command::FAILURE;
            }
        }

        $totalGenerated = 0;
        foreach ($videoSets as $videoSetPath) {
            $totalGenerated += $this->processVideoSet($videoSetPath);
        }

        $summary = count($videoSets) . ' Video-Set(s) · ' . $totalGenerated . ' Datei(en) '
            . ($this->isDryRun ? 'würden generiert' : 'generiert');

        if ($this->isDryRun) {
            $this->io->note($summary);
        } else {
            $this->io->success($summary);
        }

        return Command::SUCCESS;
    }

    // -------------------------------------------------------------------------
    // Configuration loading
    // -------------------------------------------------------------------------

    private function loadConfiguration(string $projectConfigPath): bool
    {
        $defaultConfigPath = ExtensionManagementUtility::extPath('ot_sitekitbase')
            . 'Configuration/SiteKit/VideoProcessing.yaml';

        if (!file_exists($defaultConfigPath)) {
            $this->io->error('Extension-Default-Config nicht gefunden: ' . $defaultConfigPath);
            return false;
        }

        try {
            $defaultConfig = Yaml::parseFile($defaultConfigPath);
        } catch (ParseException $exception) {
            $this->io->error('Fehler beim Lesen der Default-Config: ' . $exception->getMessage());
            return false;
        }

        $this->configuration = is_array($defaultConfig) ? $defaultConfig : [];

        if ($projectConfigPath === '') {
            $this->io->comment('Config: ' . $defaultConfigPath . ' (Extension-Default)');
            return true;
        }

        $resolvedProjectPath = $this->resolveFilePath($projectConfigPath);
        if (!file_exists($resolvedProjectPath)) {
            $this->io->error('Projekt-Config nicht gefunden: ' . $projectConfigPath);
            return false;
        }

        try {
            $projectConfig = Yaml::parseFile($resolvedProjectPath);
        } catch (ParseException $exception) {
            $this->io->error('Fehler beim Lesen der Projekt-Config: ' . $exception->getMessage());
            return false;
        }

        if (is_array($projectConfig)) {
            $this->configuration = $this->deepMerge($this->configuration, $projectConfig);
        }

        $this->io->comment('Config: ' . $resolvedProjectPath);
        return true;
    }

    /**
     * @param array<string, mixed> $base
     * @param array<string, mixed> $override
     * @return array<string, mixed>
     */
    private function deepMerge(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                $base[$key] = $this->deepMerge($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }
        return $base;
    }

    // -------------------------------------------------------------------------
    // Pre-flight checks
    // -------------------------------------------------------------------------

    private function runPreflightChecks(): bool
    {
        $this->io->section('Pre-flight');
        $passed = true;

        // ffmpeg / ffprobe (not needed for dry-run)
        if (!$this->isDryRun) {
            $configuredBinary = (string)($this->configuration['ffmpegBinary'] ?? '');

            $ffmpegPath = $this->detectBinary('ffmpeg', $configuredBinary);
            if ($ffmpegPath === null) {
                $this->io->error([
                    'ffmpeg nicht gefunden.',
                    'In DDEV: .ddev/config.yaml → webimage_extra_packages: [ffmpeg], dann: ddev restart',
                    'Oder: ffmpegBinary in VideoProcessing.yaml auf den absoluten Pfad setzen.',
                ]);
                $passed = false;
            } else {
                $this->ffmpegBinary = $ffmpegPath;
                $version = $this->getFfmpegVersion($ffmpegPath);
                $this->io->writeln('  <info>✓</info>  ffmpeg ' . $version . ' gefunden');
            }

            // ffprobe is always shipped alongside ffmpeg
            $configuredProbe = $configuredBinary !== ''
                ? (string)preg_replace('/ffmpeg([^\/]*)$/', 'ffprobe$1', $configuredBinary)
                : '';
            $ffprobePath = $this->detectBinary('ffprobe', $configuredProbe);
            if ($ffprobePath === null) {
                $this->io->error('ffprobe nicht gefunden (wird normalerweise mit ffmpeg mitgeliefert).');
                $passed = false;
            } else {
                $this->ffprobeBinary = $ffprobePath;
            }
        }

        // rootFolder
        $rootFolderConfig = (string)($this->configuration['rootFolder'] ?? '');
        if ($rootFolderConfig === '') {
            $this->io->error([
                'rootFolder ist nicht konfiguriert.',
                'Setze rootFolder in einer Projekt-Config (--config) oder nutze --folder.',
            ]);
            $passed = false;
        } else {
            $resolvedRoot = $this->resolveRootFolder($rootFolderConfig);
            if ($resolvedRoot === null) {
                $projectRoot = Environment::getProjectPath();
                $this->io->error([
                    'rootFolder nicht gefunden: ' . $rootFolderConfig,
                    'Geprüfte Pfade:',
                    '  ' . $projectRoot . '/' . ltrim($rootFolderConfig, '/') . '  (nicht gefunden)',
                    '  ' . $projectRoot . '/public/' . ltrim($rootFolderConfig, '/') . '  (nicht gefunden)',
                    'Ordner anlegen oder rootFolder in der Config anpassen.',
                ]);
                $passed = false;
            } else {
                $this->io->writeln('  <info>✓</info>  Scanning: ' . $resolvedRoot);
            }
        }

        return $passed;
    }

    private function detectBinary(string $name, string $configured): ?string
    {
        if ($configured !== '') {
            return is_executable($configured) ? $configured : null;
        }
        $process = new Process(['which', $name]);
        $process->run();
        if ($process->isSuccessful()) {
            $path = trim($process->getOutput());
            return $path !== '' ? $path : null;
        }
        return null;
    }

    private function getFfmpegVersion(string $binary): string
    {
        $process = new Process([$binary, '-version']);
        $process->run();
        if ($process->isSuccessful() && preg_match('/ffmpeg version (\S+)/', $process->getOutput(), $matches)) {
            return $matches[1];
        }
        return '(version unknown)';
    }

    // -------------------------------------------------------------------------
    // Disk space estimation
    // -------------------------------------------------------------------------

    /**
     * @param string[] $videoSets
     */
    private function checkDiskSpace(array $videoSets, string $rootFolder): bool
    {
        $totalEstimatedBytes = 0;

        $mp4Enabled = (bool)($this->configuration['formats']['mp4'] ?? true);
        $webmEnabled = (bool)($this->configuration['formats']['webm'] ?? false);

        foreach ($videoSets as $videoSetPath) {
            $sourceVideo = $this->findSourceVideo($videoSetPath);
            if ($sourceVideo === null) {
                continue;
            }
            $duration = $this->getVideoDuration($sourceVideo);
            if ($duration <= 0.0) {
                continue;
            }
            foreach ($this->getConfiguredResolutions() as $resolution) {
                $maxrateKbps = $this->parseBitrateKbps((string)($resolution['maxrate'] ?? $resolution['videoBitrate'] ?? '2500k'));
                $audioBitrateKbps = $this->parseBitrateKbps((string)($resolution['audioBitrate'] ?? '128k'));
                // bytes = totalKbps * 1000 / 8 * duration, add 20 % safety margin
                $perResolutionBytes = (int)(($maxrateKbps + $audioBitrateKbps) * 1000 / 8 * $duration * 1.2);
                if ($mp4Enabled) {
                    $totalEstimatedBytes += $perResolutionBytes;
                }
                // VP9 WebM is typically ~30% smaller than H.264 at equivalent settings
                if ($webmEnabled) {
                    $totalEstimatedBytes += (int)($perResolutionBytes * 0.7);
                }
            }
        }

        if ($totalEstimatedBytes === 0) {
            return true;
        }

        $freeBytes = disk_free_space($rootFolder);
        if ($freeBytes === false) {
            $this->io->warning('Freier Speicherplatz konnte nicht ermittelt werden — Prüfung übersprungen.');
            return true;
        }

        $freeBytes = (int)$freeBytes;

        if ($totalEstimatedBytes > $freeBytes) {
            $this->io->error([
                'Zu wenig Speicherplatz.',
                'Benötigt (Schätzung +20 %): ~' . $this->formatBytes($totalEstimatedBytes),
                'Verfügbar:                   ' . $this->formatBytes($freeBytes),
                'Tipp: --force-disk überspringt diese Prüfung (auf eigene Gefahr).',
            ]);
            return false;
        }

        $this->io->writeln(sprintf(
            '  <info>✓</info>  Speicherplatz: ~%s benötigt, %s verfügbar',
            $this->formatBytes($totalEstimatedBytes),
            $this->formatBytes($freeBytes)
        ));

        return true;
    }

    // -------------------------------------------------------------------------
    // Video set discovery
    // -------------------------------------------------------------------------

    /**
     * If rootFolder itself contains video files it is treated as a single video set.
     * Otherwise its immediate subdirectories are scanned for video files.
     *
     * @return string[]  Absolute paths with trailing slash
     */
    private function resolveVideoSets(string $rootFolder): array
    {
        if ($this->folderContainsVideo($rootFolder)) {
            return [$rootFolder];
        }

        $videoSets = [];
        $entries = scandir($rootFolder);
        if ($entries === false) {
            return [];
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $fullPath = $rootFolder . $entry;
            if (is_dir($fullPath) && $this->folderContainsVideo($fullPath . '/')) {
                $videoSets[] = $fullPath . '/';
            }
        }

        return $videoSets;
    }

    private function folderContainsVideo(string $folderPath): bool
    {
        $files = scandir($folderPath);
        if ($files === false) {
            return false;
        }
        foreach ($files as $file) {
            if (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), self::VIDEO_EXTENSIONS, true)) {
                return true;
            }
        }
        return false;
    }

    // -------------------------------------------------------------------------
    // Video set processing
    // -------------------------------------------------------------------------

    private function processVideoSet(string $videoSetPath): int
    {
        $this->io->writeln('');
        $this->io->writeln('  <comment>' . basename(rtrim($videoSetPath, '/')) . '/</comment>');

        $generated = 0;

        if ((bool)($this->configuration['poster']['enabled'] ?? true)) {
            $generated += $this->processPoster($videoSetPath);
        }

        $generated += $this->processResolutions($videoSetPath);

        if ((bool)($this->configuration['metaYamlSkeleton'] ?? true)) {
            $generated += $this->processMetaYamlSkeleton($videoSetPath);
        }

        // Write/update raw: block with auto-detected technical facts.
        // Runs after skeleton creation so meta.yaml always exists when we write.
        if (!$this->isDryRun) {
            $this->updateMetaRawBlock($videoSetPath);
        }

        return $generated;
    }

    // -------------------------------------------------------------------------
    // Poster generation
    // -------------------------------------------------------------------------

    private function processPoster(string $videoSetPath): int
    {
        $format = (string)($this->configuration['poster']['format'] ?? 'jpg');
        $posterPath = $videoSetPath . 'poster.' . $format;
        $overwrite = (bool)($this->configuration['poster']['overwrite'] ?? false);

        if (file_exists($posterPath) && !$overwrite && !$this->isForce) {
            $this->io->writeln('    <info>✓</info>  poster.' . $format . '  vorhanden');
            return 0;
        }

        $sourceVideo = $this->findSourceVideo($videoSetPath);
        if ($sourceVideo === null) {
            $this->io->writeln('    <error>✗</error>  poster.' . $format . '  kein Quell-Video gefunden');
            return 0;
        }

        // Frame number is 1-based in config; ffmpeg select filter is 0-based
        $frameNumber = max(0, (int)($this->configuration['poster']['frame'] ?? 2) - 1);

        $this->io->writeln(
            '    <comment>→</comment>  poster.' . $format
            . '  wird generiert  (Frame ' . ($frameNumber + 1) . ')'
        );

        if ($this->isDryRun) {
            return 1;
        }

        $quality = max(1, min(100, (int)($this->configuration['poster']['quality'] ?? 85)));
        // Map quality 1–100 to ffmpeg -q:v 31–1 (inverse scale)
        $ffmpegQuality = max(1, (int)round(31 - $quality * 30 / 100));

        $process = new Process([
            $this->ffmpegBinary,
            '-i', $sourceVideo,
            '-vf', 'select=eq(n\\,' . $frameNumber . ')',
            '-vframes', '1',
            '-q:v', (string)$ffmpegQuality,
            '-y',
            $posterPath,
        ]);
        $process->setTimeout(60);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->io->writeln(
                '    <error>✗</error>  poster.' . $format
                . '  Fehler: ' . trim($process->getErrorOutput())
            );
            return 0;
        }

        return 1;
    }

    // -------------------------------------------------------------------------
    // Resolution generation
    // -------------------------------------------------------------------------

    private function processResolutions(string $videoSetPath): int
    {
        $sourceVideo = $this->findSourceVideo($videoSetPath);
        if ($sourceVideo === null) {
            $this->io->writeln('    <error>✗</error>  Kein Quell-Video gefunden — Auflösungen übersprungen');
            return 0;
        }

        $sourceHeight = $this->isDryRun ? PHP_INT_MAX : $this->getVideoHeight($sourceVideo);
        $sourceHasAudio = $this->isDryRun ? true : $this->hasAudioStream($sourceVideo);
        $baseName = $this->deriveBaseName($sourceVideo);
        $generated = 0;

        $mp4Enabled = (bool)($this->configuration['formats']['mp4'] ?? true);
        $webmEnabled = (bool)($this->configuration['formats']['webm'] ?? false);

        foreach ($this->getConfiguredResolutions() as $resolution) {
            if ($mp4Enabled) {
                $generated += $this->processResolution(
                    $videoSetPath,
                    $sourceVideo,
                    $sourceHeight,
                    $sourceHasAudio,
                    $baseName,
                    $resolution,
                    'mp4'
                );
            }
            if ($webmEnabled) {
                $generated += $this->processResolution(
                    $videoSetPath,
                    $sourceVideo,
                    $sourceHeight,
                    $sourceHasAudio,
                    $baseName,
                    $resolution,
                    'webm'
                );
            }
        }

        return $generated;
    }

    /**
     * @param array<string, mixed> $resolution
     */
    private function processResolution(
        string $videoSetPath,
        string $sourceVideo,
        int $sourceHeight,
        bool $sourceHasAudio,
        string $baseName,
        array $resolution,
        string $format
    ): int {
        $height = (int)($resolution['height'] ?? 0);
        $suffix = (string)($resolution['suffix'] ?? '');

        if ($height === 0 || $suffix === '') {
            return 0;
        }

        // Never upscale
        if ($height > $sourceHeight) {
            return 0;
        }

        $outputFileName = $baseName . '_' . $suffix . '.' . $format;
        $outputPath = $videoSetPath . $outputFileName;

        // Check for any existing file that already covers this resolution suffix for this format.
        // --force only regenerates files we previously generated (exact output filename match).
        // It never overwrites user-named originals like "video_720p-3mb.mp4".
        $existingVariant = $this->findExistingResolutionVariant($videoSetPath, $suffix, $format);
        if ($existingVariant !== null) {
            $isOurOwnOutput = ($existingVariant === $outputFileName);
            if (!$isOurOwnOutput || !$this->isForce) {
                $this->io->writeln('    <info>✓</info>  ' . $existingVariant . '  vorhanden (' . $suffix . ')');
                return 0;
            }
        }

        $effectiveAudioBitrate = $sourceHasAudio ? (string)($resolution['audioBitrate'] ?? '128k') : null;
        $scaleFilter = 'scale=-2:' . $height;

        if ($format === 'webm') {
            $crfWebm = (int)($resolution['crfWebm'] ?? 33);
            $deadline = (string)($resolution['deadlineWebm'] ?? 'good');
            $twoPassWebm = (bool)($resolution['twoPassWebm'] ?? false);

            $this->io->writeln(sprintf(
                '    <comment>→</comment>  %-44s wird generiert  (%dp, vp9 crf=%d, %s%s%s)',
                $outputFileName,
                $height,
                $crfWebm,
                $deadline,
                $twoPassWebm ? ', 2-pass' : '',
                $sourceHasAudio ? '' : ', kein Ton'
            ));

            if ($this->isDryRun) {
                return 1;
            }

            $success = $twoPassWebm
                ? $this->runTwoPassEncodeWebm($sourceVideo, $outputPath, $scaleFilter, $resolution['maxrate'] ?? '2500k', $effectiveAudioBitrate, $deadline)
                : $this->runSinglePassEncodeWebm($sourceVideo, $outputPath, $scaleFilter, $crfWebm, $effectiveAudioBitrate, $deadline);
        } else {
            // H.264 MP4 — CRF mode (preferred) vs. fixed videoBitrate (legacy fallback)
            $useCrf = isset($resolution['crf']);
            $crf = (int)($resolution['crf'] ?? 23);
            $maxrate = (string)($resolution['maxrate'] ?? $resolution['videoBitrate'] ?? '2500k');
            $bufsize = (string)($resolution['bufsize'] ?? $resolution['videoBitrate'] ?? '5000k');
            $preset = (string)($resolution['preset'] ?? 'slow');
            $twoPass = (bool)($resolution['twoPass'] ?? false);

            $modeLabel = $useCrf ? 'crf=' . $crf . ', max=' . $maxrate : $maxrate;

            $this->io->writeln(sprintf(
                '    <comment>→</comment>  %-44s wird generiert  (%dp, %s, %s%s%s)',
                $outputFileName,
                $height,
                $modeLabel,
                $preset,
                $twoPass ? ', 2-pass' : '',
                $sourceHasAudio ? '' : ', kein Ton'
            ));

            if ($this->isDryRun) {
                return 1;
            }

            $success = $twoPass
                ? $this->runTwoPassEncode($sourceVideo, $outputPath, $scaleFilter, $maxrate, $effectiveAudioBitrate, $preset)
                : $this->runSinglePassEncode($sourceVideo, $outputPath, $scaleFilter, $crf, $useCrf, $maxrate, $bufsize, $effectiveAudioBitrate, $preset);
        }

        if (!$success) {
            $this->io->writeln('    <error>✗</error>  ' . $outputFileName . '  Fehler beim Kodieren');
            return 0;
        }

        // Sanity check: the generated file must be smaller than the source.
        // A larger output means re-encoding increased the file size — useless and wasteful.
        $generatedSize = filesize($outputPath);
        $sourceSize = filesize($sourceVideo);
        if ($generatedSize !== false && $sourceSize !== false && $generatedSize >= $sourceSize) {
            @unlink($outputPath);
            $this->io->writeln(sprintf(
                '    <comment>⚠</comment>  %s  gelöscht — Ausgabe (%s) wäre größer als Quelle (%s)',
                $outputFileName,
                $this->formatBytes((int)$generatedSize),
                $this->formatBytes((int)$sourceSize)
            ));
            return 0;
        }

        $this->io->writeln(sprintf(
            '    <info>✓</info>  %s  %s',
            $outputFileName,
            $generatedSize !== false ? $this->formatBytes((int)$generatedSize) : ''
        ));

        return 1;
    }

    private function runSinglePassEncode(
        string $sourceVideo,
        string $outputPath,
        string $scaleFilter,
        int $crf,
        bool $useCrf,
        string $maxrate,
        string $bufsize,
        ?string $audioBitrate,
        string $preset
    ): bool {
        $args = [
            $this->ffmpegBinary,
            '-i', $sourceVideo,
            '-vf', $scaleFilter,
            '-c:v', 'libx264',
            '-preset', $preset,
        ];

        if ($useCrf) {
            $args = array_merge($args, ['-crf', (string)$crf, '-maxrate', $maxrate, '-bufsize', $bufsize]);
        } else {
            $args = array_merge($args, ['-b:v', $maxrate]);
        }

        if ($audioBitrate !== null) {
            $args = array_merge($args, ['-c:a', 'aac', '-b:a', $audioBitrate]);
        } else {
            $args[] = '-an';
        }

        $args = array_merge($args, [
            '-movflags', '+faststart',
            '-y',
            $outputPath,
        ]);

        $process = new Process($args);
        $process->setTimeout(3600);
        $process->run();
        return $process->isSuccessful();
    }

    private function runTwoPassEncode(
        string $sourceVideo,
        string $outputPath,
        string $scaleFilter,
        string $maxrate,
        ?string $audioBitrate,
        string $preset
    ): bool {
        $passLogFile = sys_get_temp_dir() . '/sitekit_video_' . md5($outputPath);

        // 2-pass encoding is incompatible with CRF mode — ffmpeg requires a target bitrate (-b:v).
        // When CRF is configured, use maxrate as the target bitrate for both passes.
        $videoArgs = ['-vf', $scaleFilter, '-c:v', 'libx264', '-preset', $preset, '-b:v', $maxrate];

        // Pass 1 — analyse only, no audio, discard output
        $pass1 = new Process(array_merge(
            [$this->ffmpegBinary, '-y', '-i', $sourceVideo],
            $videoArgs,
            ['-pass', '1', '-passlogfile', $passLogFile, '-an', '-f', 'null', '/dev/null']
        ));
        $pass1->setTimeout(3600);
        $pass1->run();
        if (!$pass1->isSuccessful()) {
            return false;
        }

        // Pass 2 — full encode, with or without audio depending on source
        $audioArgs = $audioBitrate !== null
            ? ['-c:a', 'aac', '-b:a', $audioBitrate]
            : ['-an'];

        $pass2 = new Process(array_merge(
            [$this->ffmpegBinary, '-y', '-i', $sourceVideo],
            $videoArgs,
            ['-pass', '2', '-passlogfile', $passLogFile],
            $audioArgs,
            ['-movflags', '+faststart', $outputPath]
        ));
        $pass2->setTimeout(3600);
        $pass2->run();

        // Clean up pass log files
        foreach (glob($passLogFile . '-*.log') ?: [] as $logFile) {
            @unlink($logFile);
        }

        return $pass2->isSuccessful();
    }

    private function runSinglePassEncodeWebm(
        string $sourceVideo,
        string $outputPath,
        string $scaleFilter,
        int $crfWebm,
        ?string $audioBitrate,
        string $deadline
    ): bool {
        // VP9 CRF mode: -crf {value} -b:v 0 (b:v 0 enables constrained quality mode in libvpx-vp9)
        $args = [
            $this->ffmpegBinary,
            '-i', $sourceVideo,
            '-vf', $scaleFilter,
            '-c:v', 'libvpx-vp9',
            '-crf', (string)$crfWebm,
            '-b:v', '0',
            '-deadline', $deadline,
        ];

        if ($audioBitrate !== null) {
            $args = array_merge($args, ['-c:a', 'libopus', '-b:a', $audioBitrate]);
        } else {
            $args[] = '-an';
        }

        $args = array_merge($args, ['-y', $outputPath]);

        $process = new Process($args);
        $process->setTimeout(3600);
        $process->run();
        return $process->isSuccessful();
    }

    private function runTwoPassEncodeWebm(
        string $sourceVideo,
        string $outputPath,
        string $scaleFilter,
        string $targetBitrate,
        ?string $audioBitrate,
        string $deadline
    ): bool {
        $passLogFile = sys_get_temp_dir() . '/sitekit_video_' . md5($outputPath);

        // VP9 2-pass requires a target bitrate (-b:v), not CRF.
        $videoArgs = ['-vf', $scaleFilter, '-c:v', 'libvpx-vp9', '-b:v', $targetBitrate, '-deadline', $deadline];

        // Pass 1 — analyse only, no audio, discard output
        $pass1 = new Process(array_merge(
            [$this->ffmpegBinary, '-y', '-i', $sourceVideo],
            $videoArgs,
            ['-pass', '1', '-passlogfile', $passLogFile, '-an', '-f', 'null', '/dev/null']
        ));
        $pass1->setTimeout(3600);
        $pass1->run();
        if (!$pass1->isSuccessful()) {
            return false;
        }

        // Pass 2 — full encode, with or without audio depending on source
        $audioArgs = $audioBitrate !== null
            ? ['-c:a', 'libopus', '-b:a', $audioBitrate]
            : ['-an'];

        $pass2 = new Process(array_merge(
            [$this->ffmpegBinary, '-y', '-i', $sourceVideo],
            $videoArgs,
            ['-pass', '2', '-passlogfile', $passLogFile],
            $audioArgs,
            [$outputPath]
        ));
        $pass2->setTimeout(3600);
        $pass2->run();

        // Clean up pass log files
        foreach (glob($passLogFile . '-*.log') ?: [] as $logFile) {
            @unlink($logFile);
        }

        return $pass2->isSuccessful();
    }

    // -------------------------------------------------------------------------
    // meta.yaml skeleton
    // -------------------------------------------------------------------------

    private function processMetaYamlSkeleton(string $videoSetPath): int
    {
        $metaPath = $videoSetPath . 'meta.yaml';

        if (file_exists($metaPath)) {
            $this->io->writeln('    <info>✓</info>  meta.yaml  vorhanden');
            return 0;
        }

        $this->io->writeln('    <comment>→</comment>  meta.yaml  Skeleton wird angelegt');

        if ($this->isDryRun) {
            return 1;
        }

        file_put_contents($metaPath, self::META_YAML_SKELETON . "\n");
        return 1;
    }

    // -------------------------------------------------------------------------
    // ffprobe helpers
    // -------------------------------------------------------------------------

    /**
     * Finds an existing video file in the folder that covers the given resolution suffix.
     * When $extension is given (e.g. 'mp4', 'webm'), only files with that extension are considered.
     * Matches "_720p" at word boundary — catches "_720p.mp4", "_720p-3mb.mp4", etc.
     */
    private function findExistingResolutionVariant(string $videoSetPath, string $suffix, string $extension = ''): ?string
    {
        $files = scandir($videoSetPath);
        if ($files === false) {
            return null;
        }
        foreach ($files as $file) {
            $fileExtension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (!in_array($fileExtension, self::VIDEO_EXTENSIONS, true)) {
                continue;
            }
            if ($extension !== '' && $fileExtension !== strtolower($extension)) {
                continue;
            }
            $nameLower = strtolower(pathinfo($file, PATHINFO_FILENAME));
            if (preg_match('/_' . preg_quote(strtolower($suffix), '/') . '([^a-z0-9]|$)/', $nameLower)) {
                return $file;
            }
        }
        return null;
    }

    private function getVideoDuration(string $videoPath): float
    {
        $process = new Process([
            $this->ffprobeBinary,
            '-v', 'quiet',
            '-print_format', 'json',
            '-show_format',
            $videoPath,
        ]);
        $process->setTimeout(30);
        $process->run();

        if (!$process->isSuccessful()) {
            return 0.0;
        }

        $data = json_decode($process->getOutput(), true);
        return (float)(is_array($data) ? ($data['format']['duration'] ?? 0) : 0);
    }

    private function hasAudioStream(string $videoPath): bool
    {
        $process = new Process([
            $this->ffprobeBinary,
            '-v', 'quiet',
            '-print_format', 'json',
            '-show_streams',
            '-select_streams', 'a:0',
            $videoPath,
        ]);
        $process->setTimeout(30);
        $process->run();

        if (!$process->isSuccessful()) {
            return false;
        }

        $data = json_decode($process->getOutput(), true);
        return is_array($data) && !empty($data['streams']);
    }

    private function getVideoHeight(string $videoPath): int
    {
        $process = new Process([
            $this->ffprobeBinary,
            '-v', 'quiet',
            '-print_format', 'json',
            '-show_streams',
            '-select_streams', 'v:0',
            $videoPath,
        ]);
        $process->setTimeout(30);
        $process->run();

        if (!$process->isSuccessful()) {
            return 0;
        }

        $data = json_decode($process->getOutput(), true);
        return (int)(is_array($data) ? ($data['streams'][0]['height'] ?? 0) : 0);
    }

    /**
     * Returns [width, height] of the first video stream. Both 0 when undetectable.
     *
     * @return array{0: int, 1: int}
     */
    private function getVideoDimensions(string $videoPath): array
    {
        $process = new Process([
            $this->ffprobeBinary,
            '-v', 'quiet',
            '-print_format', 'json',
            '-show_streams',
            '-select_streams', 'v:0',
            $videoPath,
        ]);
        $process->setTimeout(30);
        $process->run();

        if (!$process->isSuccessful()) {
            return [0, 0];
        }

        $data = json_decode($process->getOutput(), true);
        return [
            (int)(is_array($data) ? ($data['streams'][0]['width'] ?? 0) : 0),
            (int)(is_array($data) ? ($data['streams'][0]['height'] ?? 0) : 0),
        ];
    }

    /**
     * Derives a simplified aspect ratio string (e.g. "16:9") from pixel dimensions via GCD.
     */
    private function aspectRatioFromDimensions(int $width, int $height): string
    {
        if ($width <= 0 || $height <= 0) {
            return '';
        }
        $divisor = $this->gcd($width, $height);
        return ($width / $divisor) . ':' . ($height / $divisor);
    }

    private function gcd(int $a, int $b): int
    {
        while ($b !== 0) {
            [$a, $b] = [$b, $a % $b];
        }
        return abs($a);
    }

    /**
     * Converts a float seconds value to ISO 8601 duration string (e.g. "PT1M30S").
     */
    private function formatDurationIso8601(float $seconds): string
    {
        $totalSeconds = (int)round($seconds);
        $hours   = intdiv($totalSeconds, 3600);
        $minutes = intdiv($totalSeconds % 3600, 60);
        $secs    = $totalSeconds % 60;

        $result = 'PT';
        if ($hours > 0) {
            $result .= $hours . 'H';
        }
        if ($minutes > 0) {
            $result .= $minutes . 'M';
        }
        $result .= $secs . 'S';
        return $result;
    }

    /**
     * Writes or updates the raw: block at the end of meta.yaml with auto-detected technical facts.
     * Preserves all existing content and comments; only the raw: section is replaced.
     * The raw: block is always at the end, preceded by a distinctive comment marker.
     */
    private function updateMetaRawBlock(string $videoSetPath): void
    {
        $metaYamlPath = $videoSetPath . 'meta.yaml';
        if (!file_exists($metaYamlPath)) {
            return;
        }

        $sourceVideo = $this->findSourceVideo($videoSetPath);
        if ($sourceVideo === null) {
            return;
        }

        // Gather raw facts
        $hasAudio = $this->hasAudioStream($sourceVideo);
        [$width, $height] = $this->getVideoDimensions($sourceVideo);
        $aspectRatio = $this->aspectRatioFromDimensions($width, $height);
        $duration = $this->getVideoDuration($sourceVideo);
        $durationIso = $duration > 0.0 ? $this->formatDurationIso8601($duration) : '';

        $rawData = ['hasAudio' => $hasAudio];
        if ($aspectRatio !== '') {
            $rawData['aspectRatio'] = $aspectRatio;
        }
        if ($durationIso !== '') {
            $rawData['duration'] = $durationIso;
        }

        // Read existing content, strip any previous raw: block
        $content = file_get_contents($metaYamlPath) ?: '';
        $marker = '# --- auto-generated by sitekit:video:process';
        $markerPosition = strpos($content, "\n" . $marker);
        if ($markerPosition !== false) {
            $content = substr($content, 0, $markerPosition);
        }
        $content = rtrim($content);

        // Build new raw: block
        $lines = ['', $marker, 'raw:'];
        foreach ($rawData as $key => $value) {
            if (is_bool($value)) {
                $lines[] = '  ' . $key . ': ' . ($value ? 'true' : 'false');
            } else {
                $lines[] = '  ' . $key . ': "' . str_replace('"', '\\"', (string)$value) . '"';
            }
        }

        file_put_contents($metaYamlPath, $content . implode("\n", $lines) . "\n");
    }

    // -------------------------------------------------------------------------
    // Filename helpers
    // -------------------------------------------------------------------------

    /**
     * Finds the best source video in a folder.
     * Prefers MP4, falls back to other formats. Picks the largest file as highest quality.
     */
    private function findSourceVideo(string $videoSetPath): ?string
    {
        $files = scandir($videoSetPath);
        if ($files === false) {
            return null;
        }

        $candidates = [];
        foreach ($files as $file) {
            if (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), self::VIDEO_EXTENSIONS, true)) {
                $fullPath = $videoSetPath . $file;
                $candidates[$fullPath] = filesize($fullPath) ?: 0;
            }
        }

        if (empty($candidates)) {
            return null;
        }

        // Largest file = highest quality / resolution
        arsort($candidates);
        return array_key_first($candidates);
    }

    /**
     * Strips resolution suffix from filename to derive the base name for generated variants.
     * Examples:
     *   "video_1080p.mp4"     → "video"
     *   "video_1080p-4mb.mp4" → "video"
     *   "interview.mp4"       → "interview"
     */
    private function deriveBaseName(string $sourceVideoPath): string
    {
        $nameWithoutExtension = pathinfo(basename($sourceVideoPath), PATHINFO_FILENAME);
        $suffixPattern = implode('|', array_map('preg_quote', self::RESOLUTION_SUFFIXES));
        $cleaned = preg_replace('/(_(' . $suffixPattern . ')([^a-zA-Z0-9].*)?)$/i', '', $nameWithoutExtension);
        return $cleaned ?? $nameWithoutExtension;
    }

    // -------------------------------------------------------------------------
    // Config helpers
    // -------------------------------------------------------------------------

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getConfiguredResolutions(): array
    {
        $resolutions = $this->configuration['resolutions'] ?? [];
        return is_array($resolutions) ? $resolutions : [];
    }

    // -------------------------------------------------------------------------
    // Path resolution
    // -------------------------------------------------------------------------

    private function resolveRootFolder(string $path): ?string
    {
        if ($path === '') {
            return null;
        }

        $normalized = rtrim($path, '/') . '/';

        if (str_starts_with($normalized, '/')) {
            return is_dir($normalized) ? $normalized : null;
        }

        $projectRoot = Environment::getProjectPath();
        $candidates = [
            $projectRoot . '/' . ltrim($normalized, '/'),
            $projectRoot . '/public/' . ltrim($normalized, '/'),
        ];

        foreach ($candidates as $candidate) {
            if (is_dir($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function resolveFilePath(string $path): string
    {
        if (str_starts_with($path, '/')) {
            return $path;
        }
        return Environment::getProjectPath() . '/' . ltrim($path, '/');
    }

    // -------------------------------------------------------------------------
    // Formatting helpers
    // -------------------------------------------------------------------------

    private function parseBitrateKbps(string $bitrate): int
    {
        return (int)rtrim(strtolower($bitrate), 'k');
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 ** 3) {
            return round($bytes / 1024 ** 3, 1) . ' GB';
        }
        if ($bytes >= 1024 ** 2) {
            return round($bytes / 1024 ** 2, 0) . ' MB';
        }
        return round($bytes / 1024, 0) . ' KB';
    }
}
