<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Throwable;
use setasign\Fpdi\Fpdi;
use Smalot\PdfParser\Parser as PdfParser;

class DocumentToolService
{
    public function __construct(
        private readonly TemporaryDownloadService $downloads,
    ) {
    }

    public function pdfToWord(UploadedFile $file): array
    {
        $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        try {
            $conversion = $this->convertPdfToWordWithLibreOffice($file->getRealPath());
            $documentPath = (string) ($conversion['docxPath'] ?? '');
            $workDir = (string) ($conversion['workDir'] ?? '');
            $downloadName = $baseName.'.docx';
            $download = $this->downloads->storeFile($documentPath, $downloadName);

            if ($workDir !== '' && is_dir($workDir)) {
                File::deleteDirectory($workDir);
            }

            $download['note'] = 'DOCX generated with LibreOffice conversion for improved compatibility.';

            return $this->response('PDF converted to a DOCX document.', [$download]);
        } catch (RuntimeException $libreOfficeError) {
            $layout = $this->extractPdfLayoutWithPhp($file->getRealPath());
            $pages = $layout['pages'] ?? [];
            $layoutTempDir = (string) ($layout['_tempDir'] ?? '');

            if ($pages === []) {
                if ($layoutTempDir !== '' && is_dir($layoutTempDir)) {
                    File::deleteDirectory($layoutTempDir);
                }

                throw new RuntimeException(
                    'PDF to Word conversion failed. LibreOffice details: '.$libreOfficeError->getMessage().
                    ' Fallback could not extract text/OCR output. Install Tesseract and Poppler (pdftoppm) for scanned PDFs.'
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
                $download['note'] = 'LibreOffice conversion was unavailable for this PDF. Fallback rebuilt DOCX using extracted text and OCR where available.';
            } finally {
                if ($layoutTempDir !== '' && is_dir($layoutTempDir)) {
                    File::deleteDirectory($layoutTempDir);
                }
            }

            return $this->response('PDF converted to a DOCX document (fallback mode).', [$download]);
        }
    }

    public function wordToPdf(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, ['doc', 'docx'], true)) {
            throw new RuntimeException('Unsupported Word format. Use DOC or DOCX.');
        }

        $conversion = $this->convertWordToPdfWithPhp($file->getRealPath(), $extension);
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

    private function extractPdfLayoutWithPhp(string $pdfPath): array
    {
        try {
            $document = (new PdfParser())->parseFile($pdfPath);
            $rawPages = $document->getPages();
        } catch (Throwable $exception) {
            throw new RuntimeException('Unable to read PDF content for Word export.', 0, $exception);
        }

        $pages = [];

        foreach ($rawPages as $pageIndex => $page) {
            $text = trim((string) $page->getText());
            $lines = preg_split('/\R+/u', $text) ?: [];
            $paragraphs = [];
            $y = 40.0;

            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') {
                    $y += 12.0;
                    continue;
                }

                $paragraphs[] = [
                    'x' => 36.0,
                    'y' => $y,
                    'width' => 744.0,
                    'height' => 18.0,
                    'lineHeight' => 18.0,
                    'alignment' => 'left',
                    'lines' => [[
                        'runs' => [[
                            'text' => $line,
                            'fontName' => 'Calibri',
                            'fontSize' => 12,
                            'fontWeight' => 'normal',
                            'fontStyle' => 'normal',
                        ]],
                    ]],
                ];

                $y += 18.0;
            }

            $pages[] = [
                'pageNumber' => $pageIndex + 1,
                'width' => 816.0,
                'height' => 1056.0,
                'type' => 'text',
                'paragraphs' => $paragraphs,
            ];
        }

        if ($this->hasTextParagraphs($pages)) {
            return [
                'pages' => $pages,
                '_tempDir' => '',
            ];
        }

        $ocrLayout = $this->extractPdfLayoutWithOcrCli($pdfPath);
        if (($ocrLayout['pages'] ?? []) !== []) {
            return $ocrLayout;
        }

        return [
            'pages' => $pages,
            '_tempDir' => '',
        ];
    }

    private function convertPdfToWordWithLibreOffice(string $pdfPath): array
    {
        $outputDir = storage_path('app/temp/pdf-word-lo-'.uniqid());
        $sourcePath = $outputDir.DIRECTORY_SEPARATOR.'source.pdf';
        $timeout = (int) config('tools.document.pdf_to_word_timeout', 240);
        $configuredBin = (string) config('tools.document.libreoffice_bin', env('LIBREOFFICE_BIN', ''));
        $sofficeBin = $this->detectLibreOfficeBin($configuredBin);

        File::ensureDirectoryExists($outputDir);

        if (! @copy($pdfPath, $sourcePath)) {
            File::deleteDirectory($outputDir);
            throw new RuntimeException('Unable to prepare the uploaded PDF file for conversion.');
        }

        if ($sofficeBin === null) {
            File::deleteDirectory($outputDir);
            throw new RuntimeException(
                'LibreOffice was not found. Install LibreOffice and set LIBREOFFICE_BIN in .env (for example: C:\\Program Files\\LibreOffice\\program\\soffice.exe).'
            );
        }

        $profileDir = $outputDir.DIRECTORY_SEPARATOR.'lo-profile';
        File::ensureDirectoryExists($profileDir);
        $profileUri = $this->toFileUri((string) (realpath($profileDir) ?: $profileDir));

        $normalizedSoffice = $this->normalizeFilesystemPath($this->preferWindowsSofficeCom($sofficeBin));
        $normalizedOutputDir = $this->normalizeFilesystemPath((string) (realpath($outputDir) ?: $outputDir));
        $normalizedSourcePath = $this->normalizeFilesystemPath((string) (realpath($sourcePath) ?: $sourcePath));
        $envOverrides = $this->libreOfficeEnvOverrides($outputDir);

        $commands = [
            [
                $normalizedSoffice,
                '--headless',
                '--nologo',
                '--nofirststartwizard',
                '-env:UserInstallation='.$profileUri,
                '--infilter=writer_pdf_import',
                '--convert-to',
                'docx',
                '--outdir',
                $normalizedOutputDir,
                $normalizedSourcePath,
            ],
            [
                $normalizedSoffice,
                '--headless',
                '--nologo',
                '--nofirststartwizard',
                '--infilter=writer_pdf_import',
                '--convert-to',
                'docx',
                '--outdir',
                $normalizedOutputDir,
                $normalizedSourcePath,
            ],
        ];

        $errors = [];
        $converted = false;
        foreach ($commands as $command) {
            try {
                $this->runProcess($command, $timeout, $envOverrides);
                $converted = true;
                break;
            } catch (RuntimeException $exception) {
                $errors[] = $exception->getMessage();
            }
        }

        if (! $converted) {
            File::deleteDirectory($outputDir);
            throw new RuntimeException('LibreOffice failed to convert the PDF to DOCX. '.implode(' | ', $errors));
        }

        $docxPath = $this->findConvertedByExtension($sourcePath, $outputDir, 'docx');
        if ($docxPath === null || ! is_file($docxPath)) {
            File::deleteDirectory($outputDir);
            throw new RuntimeException('LibreOffice completed but DOCX output was not produced.');
        }

        return [
            'docxPath' => $docxPath,
            'workDir' => $outputDir,
        ];
    }

    private function convertWordToPdfWithPhp(string $wordPath, string $extension): array
    {
        $outputDir = storage_path('app/temp/word-pdf-'.uniqid());
        $sourcePath = $outputDir.DIRECTORY_SEPARATOR.'source.'.ltrim($extension, '.');
        $timeout = (int) config('tools.document.word_to_pdf_timeout', 180);
        $configuredBin = (string) config('tools.document.libreoffice_bin', env('LIBREOFFICE_BIN', ''));
        $sofficeBin = $this->detectLibreOfficeBin($configuredBin);

        File::ensureDirectoryExists($outputDir);

        if (! @copy($wordPath, $sourcePath)) {
            File::deleteDirectory($outputDir);
            throw new RuntimeException('Unable to prepare the uploaded Word file for conversion.');
        }

        if ($sofficeBin === null) {
            File::deleteDirectory($outputDir);
            throw new RuntimeException(
                'LibreOffice was not found. Install LibreOffice and set LIBREOFFICE_BIN in .env (for example: C:\\Program Files\\LibreOffice\\program\\soffice.exe).'
            );
        }

        $profileDir = $outputDir.DIRECTORY_SEPARATOR.'lo-profile';
        File::ensureDirectoryExists($profileDir);
        $profileUri = $this->toFileUri((string) (realpath($profileDir) ?: $profileDir));

        $normalizedSoffice = $this->normalizeFilesystemPath($this->preferWindowsSofficeCom($sofficeBin));
        $normalizedOutputDir = $this->normalizeFilesystemPath((string) (realpath($outputDir) ?: $outputDir));
        $normalizedSourcePath = $this->normalizeFilesystemPath((string) (realpath($sourcePath) ?: $sourcePath));
        $envOverrides = $this->libreOfficeEnvOverrides($outputDir);

        $commands = [
            [
                $normalizedSoffice,
                '--headless',
                '--nologo',
                '--nofirststartwizard',
                '-env:UserInstallation='.$profileUri,
                '--convert-to',
                'pdf:writer_pdf_Export',
                '--outdir',
                $normalizedOutputDir,
                $normalizedSourcePath,
            ],
            [
                $normalizedSoffice,
                '--headless',
                '--nologo',
                '--nofirststartwizard',
                '--convert-to',
                'pdf:writer_pdf_Export',
                '--outdir',
                $normalizedOutputDir,
                $normalizedSourcePath,
            ],
            [
                $normalizedSoffice,
                '--headless',
                '--convert-to',
                'pdf',
                '--outdir',
                $normalizedOutputDir,
                $normalizedSourcePath,
            ],
        ];

        $localError = null;
        foreach ($commands as $command) {
            try {
                $this->runProcess($command, $timeout, $envOverrides);
                $localError = null;
                break;
            } catch (RuntimeException $exception) {
                $localError = $exception->getMessage();
            }
        }

        if ($localError === null) {
            $pdfPath = $this->findConvertedByExtension($sourcePath, $outputDir, 'pdf');
            if ($pdfPath !== null && is_file($pdfPath)) {
                return [
                    'pdfPath' => $pdfPath,
                    'workDir' => $outputDir,
                ];
            }

            $localError = 'LibreOffice finished without producing PDF output.';
        }

        try {
            $apiPdfPath = $this->convertWordToPdfWithApi($sourcePath, $outputDir);

            return [
                'pdfPath' => $apiPdfPath,
                'workDir' => $outputDir,
            ];
        } catch (RuntimeException $apiException) {
            File::deleteDirectory($outputDir);
            throw new RuntimeException(
                'Local LibreOffice conversion failed: '.$localError.' API fallback failed: '.$apiException->getMessage()
            );
        }
    }

    private function convertWordToPdfWithApi(string $sourcePath, string $outputDir): string
    {
        $enabled = (bool) config('tools.document.word_to_pdf_api_enabled', false);
        $secret = trim((string) config('tools.document.convertapi_secret', ''));
        $timeout = (int) config('tools.document.word_to_pdf_api_timeout', 120);

        if (! $enabled) {
            throw new RuntimeException('API fallback is disabled. Set WORD_TO_PDF_API_ENABLED=true in .env.');
        }

        if ($secret === '') {
            throw new RuntimeException('ConvertAPI secret is missing. Set CONVERTAPI_SECRET in .env.');
        }

        if (! is_file($sourcePath)) {
            throw new RuntimeException('API fallback could not find input Word file.');
        }

        $extension = strtolower((string) pathinfo($sourcePath, PATHINFO_EXTENSION));
        if (! in_array($extension, ['doc', 'docx'], true)) {
            throw new RuntimeException('API fallback supports only DOC and DOCX input.');
        }

        $endpoint = sprintf('https://v2.convertapi.com/convert/%s/to/pdf', $extension);

        $uploadResponse = Http::timeout(max(30, $timeout))
            ->attach('File', (string) file_get_contents($sourcePath), basename($sourcePath))
            ->post($endpoint, [
                'Secret' => $secret,
            ]);

        if (! $uploadResponse->successful()) {
            throw new RuntimeException('ConvertAPI request failed with HTTP '.$uploadResponse->status().': '.$uploadResponse->body());
        }

        $payload = $uploadResponse->json() ?: [];
        $downloadUrl = (string) data_get($payload, 'Files.0.Url', '');
        if ($downloadUrl === '') {
            throw new RuntimeException('ConvertAPI did not return a PDF download URL.');
        }

        $pdfResponse = Http::timeout(max(30, $timeout))->get($downloadUrl);
        if (! $pdfResponse->successful()) {
            throw new RuntimeException('Failed downloading PDF from ConvertAPI. HTTP '.$pdfResponse->status());
        }

        $pdfPath = $outputDir.DIRECTORY_SEPARATOR.pathinfo($sourcePath, PATHINFO_FILENAME).'.pdf';
        file_put_contents($pdfPath, $pdfResponse->body());

        if (! is_file($pdfPath) || filesize($pdfPath) === 0) {
            throw new RuntimeException('Downloaded API PDF is empty or missing.');
        }

        return $pdfPath;
    }

    private function detectLibreOfficeBin(?string $preferred = null): ?string
    {
        $candidates = [];

        if (is_string($preferred) && trim($preferred) !== '') {
            $candidates[] = trim($preferred, " \t\n\r\0\x0B\"");
        }

        $envBin = (string) env('LIBREOFFICE_BIN', '');
        if (trim($envBin) !== '') {
            $candidates[] = trim($envBin, " \t\n\r\0\x0B\"");
        }

        foreach (['soffice', 'libreoffice'] as $binaryName) {
            $resolved = $this->resolveBinaryFromPath($binaryName);
            if ($resolved !== null) {
                $candidates[] = $resolved;
            }
        }

        $candidates[] = 'C:\\Program Files\\LibreOffice\\program\\soffice.exe';
        $candidates[] = 'C:\\Program Files\\LibreOffice\\program\\soffice.com';
        $candidates[] = 'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe';
        $candidates[] = 'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.com';

        foreach (array_unique($candidates) as $candidate) {
            if ($candidate !== '' && is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function resolveBinaryFromPath(string $binaryName): ?string
    {
        $finder = DIRECTORY_SEPARATOR === '\\' ? 'where' : 'which';
        $process = new Process([$finder, $binaryName]);
        $process->setTimeout(10);
        $process->run();

        if (! $process->isSuccessful()) {
            return null;
        }

        $lines = preg_split('/\R+/u', trim($process->getOutput())) ?: [];
        foreach ($lines as $line) {
            $candidate = trim($line);
            if ($candidate !== '' && is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function findConvertedByExtension(string $inputPath, string $outputDir, string $extension): ?string
    {
        $expected = $outputDir.DIRECTORY_SEPARATOR.pathinfo($inputPath, PATHINFO_FILENAME).'.'.ltrim(strtolower($extension), '.');
        if (is_file($expected)) {
            return $expected;
        }

        $matches = glob($outputDir.DIRECTORY_SEPARATOR.'*.'.ltrim(strtolower($extension), '.')) ?: [];

        return $matches[0] ?? null;
    }

    private function runProcess(array $command, int $timeoutSeconds, array $envOverrides = []): void
    {
        $process = new Process($command);
        $process->setTimeout(max(30, $timeoutSeconds));
        if ($envOverrides !== []) {
            $inherited = getenv();
            $baseEnv = is_array($inherited) ? $inherited : [];
            $process->setEnv(array_merge($baseEnv, $envOverrides));
        }

        try {
            $process->run();
        } catch (ProcessTimedOutException $exception) {
            $cmd = implode(' ', array_map(static fn (string $part): string => '"'.$part.'"', $command));
            throw new RuntimeException('Process timed out after '.max(30, $timeoutSeconds).' seconds. Command: '.$cmd);
        }

        if ($process->isSuccessful()) {
            return;
        }

        $stderr = trim((string) $process->getErrorOutput());
        $stdout = trim((string) $process->getOutput());
        $message = trim($stderr.' '.$stdout);
        $cmd = implode(' ', array_map(static fn (string $part): string => '"'.$part.'"', $command));
        $exitCode = $process->getExitCode();

        if ($message === '') {
            $message = 'No stderr/stdout from process.';
        }

        throw new RuntimeException(
            'Command failed with exit code '.($exitCode ?? -1).'. '.$message.' Command: '.$cmd
        );
    }

    private function hasTextParagraphs(array $pages): bool
    {
        foreach ($pages as $page) {
            if (! empty($page['paragraphs'])) {
                return true;
            }
        }

        return false;
    }

    private function extractPdfLayoutWithOcrCli(string $pdfPath): array
    {
        $tesseract = $this->detectTesseractBin((string) config('tools.document.tesseract_bin', env('TESSERACT_BIN', '')));
        $pdftoppm = $this->detectPdftoppmBin((string) config('tools.document.pdftoppm_bin', env('PDFTOPPM_BIN', '')));

        if ($tesseract === null || $pdftoppm === null) {
            return [
                'pages' => [],
                '_tempDir' => '',
            ];
        }

        $outputDir = storage_path('app/temp/pdf-ocr-'.uniqid());
        File::ensureDirectoryExists($outputDir);

        try {
            $pageCount = (new Fpdi())->setSourceFile($pdfPath);
        } catch (Throwable) {
            File::deleteDirectory($outputDir);

            return [
                'pages' => [],
                '_tempDir' => '',
            ];
        }

        $pages = [];
        $timeout = (int) config('tools.document.pdf_to_word_timeout', 240);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $prefix = $outputDir.DIRECTORY_SEPARATOR.sprintf('page-%03d', $pageNo);

            try {
                $this->runProcess([
                    $pdftoppm,
                    '-f',
                    (string) $pageNo,
                    '-l',
                    (string) $pageNo,
                    '-singlefile',
                    '-png',
                    $pdfPath,
                    $prefix,
                ], $timeout);
            } catch (RuntimeException) {
                continue;
            }

            $imagePath = $prefix.'.png';
            if (! is_file($imagePath)) {
                continue;
            }

            $ocrText = '';
            try {
                $ocrText = $this->runProcessAndCaptureOutput([
                    $tesseract,
                    $imagePath,
                    'stdout',
                    '-l',
                    'eng',
                    '--psm',
                    '6',
                ], $timeout);
            } catch (RuntimeException) {
                // Keep page image; this page will stay as background-only if OCR fails.
            }

            [$width, $height] = $this->readImageSize($imagePath);
            $paragraphs = $this->buildSimpleParagraphsFromText($ocrText);

            $pages[] = [
                'pageNumber' => $pageNo,
                'width' => $width,
                'height' => $height,
                'type' => 'ocr',
                'backgroundImage' => $imagePath,
                'paragraphs' => $paragraphs,
            ];
        }

        return [
            'pages' => $pages,
            '_tempDir' => $outputDir,
        ];
    }

    private function buildSimpleParagraphsFromText(string $text): array
    {
        $paragraphs = [];
        $lines = preg_split('/\R+/u', trim($text)) ?: [];
        $y = 40.0;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                $y += 10.0;
                continue;
            }

            $paragraphs[] = [
                'x' => 36.0,
                'y' => $y,
                'width' => 744.0,
                'height' => 18.0,
                'lineHeight' => 18.0,
                'alignment' => 'left',
                'lines' => [[
                    'runs' => [[
                        'text' => $line,
                        'fontName' => 'Calibri',
                        'fontSize' => 12,
                        'fontWeight' => 'normal',
                        'fontStyle' => 'normal',
                    ]],
                ]],
            ];

            $y += 18.0;
        }

        return $paragraphs;
    }

    private function readImageSize(string $imagePath): array
    {
        $size = @getimagesize($imagePath);

        if (! is_array($size)) {
            return [816.0, 1056.0];
        }

        return [
            max(100.0, (float) ($size[0] ?? 816.0)),
            max(100.0, (float) ($size[1] ?? 1056.0)),
        ];
    }

    private function runProcessAndCaptureOutput(array $command, int $timeoutSeconds): string
    {
        $process = new Process($command);
        $process->setTimeout(max(30, $timeoutSeconds));

        try {
            $process->run();
        } catch (ProcessTimedOutException $exception) {
            $cmd = implode(' ', array_map(static fn (string $part): string => '"'.$part.'"', $command));
            throw new RuntimeException('Process timed out after '.max(30, $timeoutSeconds).' seconds. Command: '.$cmd);
        }

        if (! $process->isSuccessful()) {
            $stderr = trim((string) $process->getErrorOutput());
            $stdout = trim((string) $process->getOutput());
            $message = trim($stderr.' '.$stdout);
            $cmd = implode(' ', array_map(static fn (string $part): string => '"'.$part.'"', $command));
            $exitCode = $process->getExitCode();

            throw new RuntimeException('Command failed with exit code '.($exitCode ?? -1).'. '.$message.' Command: '.$cmd);
        }

        return trim((string) $process->getOutput());
    }

    private function detectTesseractBin(string $preferred): ?string
    {
        $candidates = array_values(array_filter([
            trim($preferred),
            (string) env('TESSERACT_BIN', ''),
            $this->resolveBinaryFromPath('tesseract'),
            'C:\\Program Files\\Tesseract-OCR\\tesseract.exe',
            'C:\\Program Files (x86)\\Tesseract-OCR\\tesseract.exe',
        ], static fn (?string $item): bool => is_string($item) && trim($item) !== ''));

        foreach (array_unique($candidates) as $candidate) {
            $path = trim((string) $candidate, " \t\n\r\0\x0B\"");
            if ($path !== '' && is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function detectPdftoppmBin(string $preferred): ?string
    {
        $candidates = array_values(array_filter([
            trim($preferred),
            (string) env('PDFTOPPM_BIN', ''),
            $this->resolveBinaryFromPath('pdftoppm'),
            'C:\\Program Files\\poppler\\Library\\bin\\pdftoppm.exe',
            'C:\\Program Files\\poppler\\bin\\pdftoppm.exe',
        ], static fn (?string $item): bool => is_string($item) && trim($item) !== ''));

        foreach (array_unique($candidates) as $candidate) {
            $path = trim((string) $candidate, " \t\n\r\0\x0B\"");
            if ($path !== '' && is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function toFileUri(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);

        if (preg_match('/^[A-Za-z]:\//', $normalized) === 1) {
            return 'file:///'.str_replace(' ', '%20', $normalized);
        }

        if (str_starts_with($normalized, '/')) {
            return 'file://'.str_replace(' ', '%20', $normalized);
        }

        return 'file:///'.str_replace(' ', '%20', $normalized);
    }

    private function normalizeFilesystemPath(string $path): string
    {
        $trimmed = trim($path, " \t\n\r\0\x0B\"");

        if ($trimmed === '') {
            return $trimmed;
        }

        if (DIRECTORY_SEPARATOR === '\\') {
            return str_replace('/', '\\', $trimmed);
        }

        return str_replace('\\', '/', $trimmed);
    }

    private function preferWindowsSofficeCom(string $binaryPath): string
    {
        if (DIRECTORY_SEPARATOR !== '\\') {
            return $binaryPath;
        }

        $normalized = $this->normalizeFilesystemPath($binaryPath);
        if (! str_ends_with(strtolower($normalized), 'soffice.exe')) {
            return $normalized;
        }

        $comPath = preg_replace('/soffice\.exe$/i', 'soffice.com', $normalized);
        if (is_string($comPath) && is_file($comPath)) {
            return $comPath;
        }

        return $normalized;
    }

    private function libreOfficeEnvOverrides(string $outputDir): array
    {
        $normalized = $this->normalizeFilesystemPath((string) (realpath($outputDir) ?: $outputDir));

        return [
            'HOME' => $normalized,
            'USERPROFILE' => $normalized,
            'TMP' => $normalized,
            'TEMP' => $normalized,
            'SAL_USE_VCLPLUGIN' => 'svp',
        ];
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
