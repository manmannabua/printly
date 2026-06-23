<?php

namespace App\Services;

use App\Models\OrderFile;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser as PdfParser;
use Throwable;

/**
 * Derives print specs (page count, paper size) from an uploaded file.
 *
 * v1 scope (planning §10): PDF page count + paper size via smalot/pdfparser.
 * Per-page colour detection and Office→PDF conversion are phase 2 — colour is a
 * whole-document flag the customer sets, not detected here. Images count as one
 * page. Anything we cannot parse is marked failed rather than throwing, so the
 * upload still succeeds and staff can handle it manually.
 */
class FileAnalysisService
{
    /** Common page sizes in PostScript points (1pt = 1/72"), within a tolerance. */
    private const PAPER_SIZES = [
        'A4' => [595.28, 841.89],
        'A3' => [841.89, 1190.55],
        'Letter' => [612.0, 792.0],
        'Legal' => [612.0, 1008.0],
    ];

    private const SIZE_TOLERANCE_PTS = 12.0; // ~4mm

    public function analyze(OrderFile $file): OrderFile
    {
        try {
            $disk = Storage::disk('private');

            if (! $disk->exists($file->storage_path)) {
                throw new \RuntimeException('Stored file is missing.');
            }

            if ($this->isPdf($file)) {
                [$pageCount, $paperSize] = $this->analyzePdf($disk->path($file->storage_path));
            } else {
                // Images / unknown: a single page, size undetermined.
                $pageCount = 1;
                $paperSize = null;
            }

            $file->update([
                'page_count' => $pageCount,
                'paper_size' => $paperSize,
                'analysis_status' => OrderFile::ANALYSIS_DONE,
                'analysis_error' => null,
            ]);
        } catch (Throwable $e) {
            $file->update([
                'analysis_status' => OrderFile::ANALYSIS_FAILED,
                'analysis_error' => $e->getMessage(),
            ]);
        }

        return $file;
    }

    private function isPdf(OrderFile $file): bool
    {
        return $file->mime === 'application/pdf'
            || str_ends_with(strtolower($file->original_name), '.pdf');
    }

    /**
     * @return array{0:int, 1:?string} [pageCount, paperSize]
     */
    private function analyzePdf(string $absolutePath): array
    {
        $pdf = (new PdfParser)->parseFile($absolutePath);
        $pages = $pdf->getPages();
        $pageCount = max(1, count($pages));

        $paperSize = null;
        $first = $pages[0] ?? null;
        if ($first) {
            $details = $first->getDetails();
            // MediaBox is [x0, y0, x1, y1] in points.
            $box = $details['MediaBox'] ?? null;
            if (is_array($box) && count($box) === 4) {
                $width = abs((float) $box[2] - (float) $box[0]);
                $height = abs((float) $box[3] - (float) $box[1]);
                $paperSize = $this->matchPaperSize($width, $height);
            }
        }

        return [$pageCount, $paperSize];
    }

    private function matchPaperSize(float $width, float $height): ?string
    {
        // Normalise to portrait so landscape matches the same size.
        $w = min($width, $height);
        $h = max($width, $height);

        foreach (self::PAPER_SIZES as $name => [$pw, $ph]) {
            if (abs($w - $pw) <= self::SIZE_TOLERANCE_PTS && abs($h - $ph) <= self::SIZE_TOLERANCE_PTS) {
                return $name;
            }
        }

        return null;
    }
}
