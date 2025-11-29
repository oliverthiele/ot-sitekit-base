<?php

declare(strict_types=1);

namespace OliverThiele\OtSitekitbase\ViewHelpers;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class GetImageInfoViewHelper extends AbstractViewHelper implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var bool
     */
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('image', 'mixed', 'Path to file or FileReference object', true);
        $this->registerArgument('data', 'array', 'The tt_content data array for crop variants', false, []);
        $this->registerArgument('defaultCropVariant', 'string', 'Fallback crop variant identifier', false, 'default');
        $this->registerArgument('maxWidth', 'mixed', 'Explicit width in pixels (int or array)', false, null);
        $this->registerArgument('numColumns', 'mixed', 'Number of columns to divide container width by (int or array)', false, null);
    }

    /**
     * @return array{exists: bool, publicUrl: string, variants: array<string, string>, widths: array<string, int>, suggestPictureTag: bool, ratioClass: string, metadata: array, original: mixed}
     */
    public function render(): array
    {
        $image = $this->arguments['image'];
        $data = $this->arguments['data'];
        $defaultCrop = $this->arguments['defaultCropVariant'];
        $maxWidthInput = $this->arguments['maxWidth'];
        $numColumnsInput = $this->arguments['numColumns'];

        $result = [
            'exists' => false,
            'publicUrl' => '',
            'variants' => [],
            'widths' => [],
            'suggestPictureTag' => false,
            'ratioClass' => '',
            'metadata' => [
                'uid' => 0,
                'alternative' => '',
                'title' => '',
                'description' => '',
                'link' => '',
            ],
            'original' => $image
        ];

        // 1. Existenzprüfung
        if (empty($image)) {
            return $result;
        }

        try {
            if ($image instanceof FileReference) {
                $originalFile = $image->getOriginalFile();
                if (!$originalFile instanceof FileInterface || !$originalFile->exists()) {
                    $this->logger->warning('Referenced file is missing for FileReference UID: ' . $image->getUid());
                    return $result;
                }
                $result['publicUrl'] = $image->getPublicUrl();
                $result['metadata']['uid'] = $image->getUid();
                $result['metadata']['alternative'] = $image->getAlternative();
                $result['metadata']['title'] = $image->getTitle();
                $result['metadata']['description'] = $image->getDescription();
                $result['metadata']['link'] = $image->getLink();
            } elseif (is_string($image)) {
                $absPath = GeneralUtility::getFileAbsFileName($image);
                if (empty($absPath) || !file_exists($absPath)) {
                    $this->logger->warning('File not found at path: ' . $image);
                    return $result;
                }
                $result['publicUrl'] = $image;
            } else {
                return $result;
            }
        } catch (\Throwable $e) {
            $this->logger->error('Error resolving image: ' . $e->getMessage());
            return $result;
        }

        $result['exists'] = true;


        // 2. Breiten-Berechnung (Smart Inheritance & Columns)
        $breakpoints = ['xs', 'sm', 'md', 'lg', 'xl', 'xxl'];

        $defaultBootstrapWidths = [
            'xs' => 551,
            'sm' => 516,
            'md' => 696,
            'lg' => 936,
            'xl' => 1116,
            'xxl' => 1296
        ];

        // Inputs normalisieren
        $inputWidths = [];
        if (is_array($maxWidthInput)) {
            $inputWidths = $maxWidthInput;
        } elseif (is_numeric($maxWidthInput) || is_string($maxWidthInput)) {
            $inputWidths = ['xs' => (int)$maxWidthInput];
        }

        $inputCols = [];
        if (is_array($numColumnsInput)) {
            $inputCols = $numColumnsInput;
        } elseif (is_numeric($numColumnsInput)) {
            $inputCols = ['xs' => (int)$numColumnsInput];
        }

        // State Tracking für Vererbung
        // Wir tracken entweder eine explizite Pixelbreite ODER eine Spaltenanzahl
        $currentColCount = 1;
        $currentPixelOverride = null;

        foreach ($breakpoints as $bp) {
            // 1. Check auf neue Column-Definition (setzt neuen Standard)
            if (isset($inputCols[$bp]) && $inputCols[$bp] > 0) {
                $currentColCount = (float)$inputCols[$bp]; // float erlaubt auch 1.5 Spalten theoretisch
                $currentPixelOverride = null; // Spalten-Modus gewinnt, Pixel-Override resetten
            }

            // 2. Check auf neue Pixel-Definition (setzt neuen Standard und überschreibt Col-Modus)
            if (isset($inputWidths[$bp]) && $inputWidths[$bp] > 0) {
                $currentPixelOverride = (int)$inputWidths[$bp];
            }

            // 3. Berechnung für diesen Breakpoint
            if ($currentPixelOverride !== null) {
                $result['widths'][$bp] = $currentPixelOverride;
            } else {
                // Berechne basierend auf Default Container Width / Spalten
                // ceil() verwenden, damit wir bei krummen Werten immer leicht größer sind (Performance vs. Schärfe -> Schärfe gewinnt)
                $result['widths'][$bp] = (int)ceil($defaultBootstrapWidths[$bp] / $currentColCount);
            }
        }


        // 3. Crop Varianten & Ratio Berechnung
        $currentVariant = $defaultCrop;
        $ratiosFound = [];
        
//        DebuggerUtility::var_dump($data, __METHOD__);

        foreach ($breakpoints as $bp) {
            $fieldName = 'crop_variant_' . $bp;
            if (!empty($data[$fieldName])) {
                $currentVariant = $data[$fieldName];
            }
            $result['variants'][$bp] = $currentVariant;

            if (strpos((string)$currentVariant, ':') !== false) {
                $ratiosFound[] = $currentVariant;
            } else {
                $ratiosFound[] = 'free';
            }
        }

        // Picture Tag Entscheidung
        // Sobald wir unterschiedliche Crops haben -> Picture Tag
        $uniqueVariants = array_unique($result['variants']);
        if (count($uniqueVariants) > 1) {
            $result['suggestPictureTag'] = true;
        }

        // Ratio Class Entscheidung
        // Nur wenn ALLE Breakpoints dasselbe Ratio haben
        $uniqueRatios = array_unique($ratiosFound);
        if (count($uniqueRatios) === 1 && $uniqueRatios[0] !== 'free') {
            // Wandelt z.B. 16:9 in 16x9 um für Bootstrap Ratio Klasse
            $ratioString = str_replace(':', 'x', $uniqueRatios[0]);
            $result['ratioClass'] = 'ratio ratio-' . $ratioString;
        }

        return $result;
    }
}
