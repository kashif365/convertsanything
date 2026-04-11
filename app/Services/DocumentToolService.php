<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Throwable;
use setasign\Fpdi\Fpdi;

class DocumentToolService
{
    public function __construct(
        private readonly TemporaryDownloadService $downloads,
    ) {
    }

    public function pdfToWord(UploadedFile $file): array
    {
        $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $layout = $this->extractPdfLayoutWithPython($file->getRealPath(), $baseName);
        $pages = $layout['pages'] ?? [];
        $layoutTempDir = (string) ($layout['_tempDir'] ?? '');

        if ($pages === []) {
            if ($layoutTempDir !== '' && is_dir($layoutTempDir)) {
                File::deleteDirectory($layoutTempDir);
            }

            throw new RuntimeException(
                'This PDF could not be converted. No extractable text or OCR output was produced by the current backend.'
            );
        }

        try {
            $documentPath = $this->buildWordDocumentFromPages(
                $pages,
                'Converted from PDF: '.$file->getClientOriginalName()
            );

            $downloadName = $baseName.'.docx';
            $download = $this->downloads->storeFile($documentPath, $downloadName);
            @unlink($documentPath);
            $download['note'] = 'DOCX generated with improved layout grouping, spacing-aware text runs, and OCR fallback for scanned pages.';
        } finally {
            if ($layoutTempDir !== '' && is_dir($layoutTempDir)) {
                File::deleteDirectory($layoutTempDir);
            }
        }

        return $this->response('PDF converted to a DOCX document.', [$download]);
    }

    public function wordToPdf(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, ['doc', 'docx'], true)) {
            throw new RuntimeException('Unsupported Word format. Use DOC or DOCX.');
        }

        $conversion = $this->convertWordToPdfWithPython($file->getRealPath(), $extension);
        $pdfPath = (string) ($conversion['pdfPath'] ?? '');
        $workDir = (string) ($conversion['workDir'] ?? '');

        if (! is_file($pdfPath)) {
            if ($workDir !== '' && is_dir($workDir)) {
                File::deleteDirectory($workDir);
            }

            throw new RuntimeException('Word to PDF conversion failed. No PDF output was produced.');
        }

        $downloadName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME).'.pdf';

        try {
            $download = $this->downloads->storeFile($pdfPath, $downloadName);
        } finally {
            if ($workDir !== '' && is_dir($workDir)) {
                File::deleteDirectory($workDir);
            }
        }

        $download['note'] = 'PDF generated with LibreOffice headless conversion for higher layout fidelity.';

        return $this->response('Word document converted to PDF.', [$download]);
    }

    public function mergePdfs(array $files): array
    {
        if (count($files) < 2) {
            throw new RuntimeException('Please upload at least two PDF files to merge.');
        }

        $pdf = new Fpdi();

        try {
            foreach ($files as $file) {
                $pageCount = $pdf->setSourceFile($file->getRealPath());

                for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                    $template = $pdf->importPage($pageNo);
                    $size = $pdf->getTemplateSize($template);
                    $orientation = $size['width'] > $size['height'] ? 'L' : 'P';
                    $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                    $pdf->useTemplate($template);
                }
            }
        } catch (Throwable $exception) {
            throw new RuntimeException('One of the uploaded PDFs appears corrupted or unreadable.', 0, $exception);
        }

        $download = $this->downloads->storeBinary(
            $pdf->Output('S'),
            'merged-'.date('Ymd-His').'.pdf'
        );
        $download['note'] = count($files).' PDFs merged into one file.';

        return $this->response('PDFs merged successfully.', [$download]);
    }

    public function splitPdf(UploadedFile $file, string $ranges): array
    {
        $rangeList = $this->parseRanges($ranges);
        $downloads = [];
        $source = new Fpdi();
        $pageCount = $source->setSourceFile($file->getRealPath());

        try {
            foreach ($rangeList as $index => [$start, $end]) {
                if ($start > $pageCount || $end > $pageCount) {
                    throw new RuntimeException("Range {$start}-{$end} exceeds the PDF page count ({$pageCount}).");
                }

                $pdf = new Fpdi();
                $pdf->setSourceFile($file->getRealPath());

                for ($pageNo = $start; $pageNo <= $end; $pageNo++) {
                    $template = $pdf->importPage($pageNo);
                    $size = $pdf->getTemplateSize($template);
                    $orientation = $size['width'] > $size['height'] ? 'L' : 'P';
                    $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                    $pdf->useTemplate($template);
                }

                $downloadName = sprintf(
                    '%s-part-%d-pages-%d-%d.pdf',
                    pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                    $index + 1,
                    $start,
                    $end,
                );

                $item = $this->downloads->storeBinary($pdf->Output('S'), $downloadName);
                $item['note'] = "Pages {$start}-{$end}";
                $downloads[] = $item;
            }
        } catch (Throwable $exception) {
            if ($exception instanceof RuntimeException) {
                throw $exception;
            }

            throw new RuntimeException('Failed to split PDF because the file appears corrupted or unsupported.', 0, $exception);
        }

        if ($downloads === []) {
            throw new RuntimeException('No output PDFs were created from the selected ranges.');
        }

        return $this->response('PDF split successfully.', $downloads);
    }

    private function extractPdfLayoutWithPython(string $pdfPath, string $baseName): array
    {
        $script = base_path('scripts/pdf_to_word_layout.py');
        $outputDir = storage_path('app/temp/pdf-layout-'.uniqid());
        $outputJson = storage_path('app/temp/pdf-layout-'.uniqid().'.json');
        File::ensureDirectoryExists($outputDir);

        $this->runPythonScript($script, [$pdfPath, $outputDir, $outputJson], 180);

        if (! is_file($outputJson)) {
            throw new RuntimeException('PDF layout extraction failed. Backend did not return output JSON.');
        }

        $json = file_get_contents($outputJson) ?: '{}';
        @unlink($outputJson);
        $data = json_decode($json, true);

        if (! is_array($data)) {
            throw new RuntimeException('PDF layout extraction returned invalid JSON.');
        }

        $data['_tempDir'] = $outputDir;

        return $data;
    }

    private function convertWordToPdfWithPython(string $wordPath, string $extension): array
    {
        $script = base_path('scripts/word_to_pdf_libreoffice.py');
        $outputDir = storage_path('app/temp/word-pdf-'.uniqid());
        $outputJson = storage_path('app/temp/word-pdf-'.uniqid().'.json');
        $sourcePath = $outputDir.DIRECTORY_SEPARATOR.'source.'.ltrim($extension, '.');
        $libreOfficeBin = (string) config('tools.document.libreoffice_bin', env('LIBREOFFICE_BIN', ''));
        $timeout = (int) config('tools.document.word_to_pdf_timeout', 180);
        File::ensureDirectoryExists($outputDir);

        if (! @copy($wordPath, $sourcePath)) {
            File::deleteDirectory($outputDir);
            throw new RuntimeException('Unable to prepare the uploaded Word file for conversion.');
        }

        try {
            $this->runPythonScript($script, [$sourcePath, $outputDir, $outputJson, $libreOfficeBin], $timeout);
        } catch (RuntimeException $exception) {
            if (is_file($outputJson)) {
                $payload = json_decode((string) file_get_contents($outputJson), true) ?: [];
                @unlink($outputJson);

                if (($payload['errorCode'] ?? '') === 'libreoffice_missing') {
                    File::deleteDirectory($outputDir);
                    throw new RuntimeException(
                        'LibreOffice was not found. Install LibreOffice and set LIBREOFFICE_BIN in .env (for example: C:\\Program Files\\LibreOffice\\program\\soffice.exe).'
                    );
                }

                if (! empty($payload['message'])) {
                    $details = trim((string) ($payload['details'] ?? ''));
                    File::deleteDirectory($outputDir);
                    throw new RuntimeException(
                        $details !== ''
                            ? ((string) $payload['message']).' Details: '.preg_replace('/\s+/u', ' ', $details)
                            : (string) $payload['message']
                    );
                }
            }

            File::deleteDirectory($outputDir);
            throw $exception;
        }

        $payload = json_decode((string) (file_get_contents($outputJson) ?: '{}'), true) ?: [];
        @unlink($outputJson);

        $pdfPath = (string) ($payload['pdfPath'] ?? '');
        if (! is_file($pdfPath)) {
            File::deleteDirectory($outputDir);
            throw new RuntimeException((string) ($payload['message'] ?? 'Word to PDF conversion did not produce a PDF output.'));
        }

        return [
            'pdfPath' => $pdfPath,
            'workDir' => $outputDir,
        ];
    }

    private function runPythonScript(string $script, array $arguments, int $timeoutSeconds): void
    {
        $candidates = $this->pythonCandidates();
        $timeout = max(30, $timeoutSeconds);
        $errors = [];

        foreach ($candidates as $pythonBin) {
            $process = new Process(array_merge([$pythonBin, $script], $arguments));
            $process->setTimeout($timeout);
            // Avoid interpreter startup failures related to random hash initialization on some Windows setups.
            $inheritedEnv = getenv();
            $env = is_array($inheritedEnv) ? $inheritedEnv : [];
            $process->setEnv(array_merge($env, [
                'PYTHONHASHSEED' => '0',
            ]));
            $process->run();

            if ($process->isSuccessful()) {
                return;
            }

            $output = trim($process->getErrorOutput().' '.$process->getOutput());
            $errors[] = sprintf('[%s] %s', $pythonBin, $output !== '' ? $output : (new ProcessFailedException($process))->getMessage());

            if (! str_contains(strtolower($output), 'fatal python error')) {
                break;
            }
        }

        throw new RuntimeException(implode("\n", $errors));
    }

    private function pythonCandidates(): array
    {
        $configured = (string) config('tools.document.python_bin', env('PDF_PYTHON_BIN', 'python'));

        $candidates = array_values(array_filter([
            $configured,
            'python',
        ], static fn (string $value): bool => trim($value) !== ''));

        return array_values(array_unique($candidates));
    }

    private function buildWordDocumentFromPages(array $pages, string $title): string
    {
        $this->configurePhpWordZipBackend();

        $phpWord = new PhpWord();
        $phpWord->getDocInfo()->setTitle($title);
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(11);

        foreach ($pages as $pageIndex => $page) {
            $width = (float) ($page['width'] ?? 816);
            $height = (float) ($page['height'] ?? 1056);
            $isOcr = ($page['type'] ?? '') === 'ocr';
            $section = $phpWord->addSection([
                'pageSizeW' => $this->pixelsToTwips($width),
                'pageSizeH' => $this->pixelsToTwips($height),
                'marginTop' => 0,
                'marginRight' => 0,
                'marginBottom' => 0,
                'marginLeft' => 0,
            ]);

            if (!empty($page['backgroundImage']) && is_file($page['backgroundImage'])) {
                $section->addImage($page['backgroundImage'], [
                    'width' => $this->pixelsToPoints($width),
                    'height' => $this->pixelsToPoints($height),
                    'positioning' => 'absolute',
                    'posHorizontal' => 'absolute',
                    'posHorizontalRel' => 'page',
                    'marginLeft' => 0,
                    'posVertical' => 'absolute',
                    'posVerticalRel' => 'page',
                    'marginTop' => 0,
                    'wrappingStyle' => 'behind',
                ]);
            }

            foreach (($page['paragraphs'] ?? []) as $paragraph) {
                $paragraphX = (float) ($paragraph['x'] ?? 0);
                $paragraphY = (float) ($paragraph['y'] ?? 0);
                $paragraphWidth = (float) ($paragraph['width'] ?? max(60.0, $width - $paragraphX - 16.0));
                $paragraphHeight = (float) ($paragraph['height'] ?? 0);
                $lineHeight = max(14.0, (float) ($paragraph['lineHeight'] ?? 18));
                $alignment = $this->resolveParagraphAlignment((string) ($paragraph['alignment'] ?? 'left'));
                $lines = $paragraph['lines'] ?? [];

                if ($lines === []) {
                    continue;
                }

                $textBox = $section->addTextBox([
                    'width' => $this->pixelsToPoints(max(60.0, min($paragraphWidth, $width - $paragraphX))),
                    'height' => $this->pixelsToPoints(max($lineHeight + 6.0, $paragraphHeight, count($lines) * ($lineHeight + 2.0))),
                    'positioning' => 'absolute',
                    'posHorizontal' => 'absolute',
                    'posHorizontalRel' => 'page',
                    'marginLeft' => $this->pixelsToPoints($paragraphX),
                    'posVertical' => 'absolute',
                    'posVerticalRel' => 'page',
                    'marginTop' => $this->pixelsToPoints($paragraphY),
                    'wrappingStyle' => 'infront',
                    'borderSize' => 0,
                    'innerMargin' => 0,
                ]);

                foreach ($lines as $lineIndex => $line) {
                    $paragraphStyle = [
                        'spaceAfter' => 0,
                        'spaceBefore' => 0,
                        'lineSpacing' => $this->pixelsToTwips($lineHeight),
                        'alignment' => $alignment,
                    ];
                    $textRun = $textBox->addTextRun($paragraphStyle);
                    $runs = $line['runs'] ?? [];

                    if ($runs === []) {
                        $textRun->addText(' ', $this->runFontStyle([], $isOcr));
                    } else {
                        foreach ($runs as $run) {
                            $textRun->addText(
                                $this->cleanWordText((string) ($run['text'] ?? '')),
                                $this->runFontStyle($run, $isOcr)
                            );
                        }
                    }

                    if ($lineIndex < count($lines) - 1) {
                        $textBox->addTextBreak();
                    }
                }
            }

        }

        $outputPath = storage_path('app/temp/'.uniqid('pdf-word-', true).'.docx');
        File::ensureDirectoryExists(dirname($outputPath));
        IOFactory::createWriter($phpWord, 'Word2007')->save($outputPath);

        return $outputPath;
    }


    private function parseRanges(string $ranges): array
    {
        $result = [];

        foreach (explode(',', $ranges) as $chunk) {
            [$start, $end] = array_map('intval', explode('-', trim($chunk), 2) + [1 => 0]);

            if ($start < 1 || $end < $start) {
                throw new RuntimeException('Invalid page range: '.$chunk);
            }

            $result[] = [$start, $end];
        }

        if ($result === []) {
            throw new RuntimeException('Please provide at least one valid page range.');
        }

        usort($result, static fn (array $a, array $b) => $a[0] <=> $b[0]);

        for ($index = 1; $index < count($result); $index++) {
            $previous = $result[$index - 1];
            $current = $result[$index];

            if ($current[0] <= $previous[1]) {
                throw new RuntimeException('Page ranges must not overlap.');
            }
        }

        return $result;
    }

    private function response(string $message, array $downloads): array
    {
        $first = $downloads[0] ?? ['filename' => null, 'downloadUrl' => null];

        return [
            'success' => true,
            'message' => $message,
            'filename' => $first['filename'],
            'downloadUrl' => $first['downloadUrl'],
            'downloads' => $downloads,
        ];
    }

    private function runFontStyle(array $run, bool $isOcr): array
    {
        $style = [
            'name' => (string) ($run['fontName'] ?? 'Calibri'),
            'size' => max(9, (int) round($this->pixelsToPoints((float) ($run['fontSize'] ?? 12)))),
            'bold' => ($run['fontWeight'] ?? 'normal') === 'bold',
            'italic' => ($run['fontStyle'] ?? 'normal') === 'italic',
        ];

        if ($isOcr) {
            $style['color'] = 'FFFFFF';
        }

        return $style;
    }

    private function cleanWordText(string $text): string
    {
        return str_replace(["\r", "\u{00A0}"], ['', ' '], $text);
    }

    private function resolveParagraphAlignment(string $alignment): string
    {
        return match (strtolower($alignment)) {
            'center' => 'center',
            'right' => 'right',
            default => 'left',
        };
    }

    private function pixelsToPoints(float $pixels): float
    {
        return round($pixels * 0.75, 2);
    }

    private function pixelsToTwips(float $pixels): int
    {
        return (int) round($pixels * 15);
    }

    private function configurePhpWordZipBackend(): void
    {
        if (class_exists(\ZipArchive::class)) {
            Settings::setZipClass(Settings::ZIPARCHIVE);

            return;
        }

        if (! class_exists('PclZip')) {
            require_once base_path('vendor/phpoffice/phpword/src/PhpWord/Shared/PCLZip/pclzip.lib.php');
        }

        Settings::setZipClass(Settings::PCLZIP);
    }
}
