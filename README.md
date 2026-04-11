# ConvertsAnything - Laravel Blade Conversion

This project is a Laravel Blade conversion of an uploaded Next.js/TypeScript tools app.

It keeps the same product idea and tool set, and now includes the missing Laravel bootstrap files required to run this repository directly.

## What was converted

The original uploaded project used:
- Next.js App Router
- TypeScript `.tsx` pages and components
- Tailwind-based UI
- Browser-side image/text tools
- Laravel API calls for document tools

This converted version uses:
- Blade templates in `resources/views`
- Laravel routes in `routes/web.php` and `routes/api.php`
- Laravel controllers in `app/Http/Controllers`
- Plain CSS in `public/assets/app.css`
- Plain JavaScript in `public/assets/app.js`
- Config-driven tool metadata in `config/tools.php`

## Important reality check

The original conversion was not a complete Laravel application. It had the app-specific pages, routes, controllers, CSS, and JS, but it was missing core Laravel runtime files.

The missing framework pieces have now been restored in this repository, so the app can boot locally. The project is now runnable as a Laravel 12 app from this folder.

The app now includes a working backend for image, text, and document tools in this repository.

## Changes added to make this repo runnable

These changes were added in this repo:

- added `artisan`
- added `bootstrap/app.php`
- added `bootstrap/providers.php`
- added `public/index.php`
- added `routes/console.php`
- added `app/Http/Controllers/Controller.php`
- added missing Laravel runtime directories under `storage/framework`, `storage/logs`, and `bootstrap/cache`
- fixed PHP BOM/encoding issues in controllers so namespaces load correctly
- generated an application key in `.env`
- verified bootstrapping with `php artisan about`
- verified routing with `php artisan route:list`
- verified local HTTP response from `http://127.0.0.1:8000`

## Backend added after frontend integration

The frontend is now backed by working Laravel APIs for all tools shown in the UI.

### Image backend

Added server-side image processing with `Intervention Image` for:
- JPG to PNG
- PNG to WebP
- image compression
- image resizing

Files added and updated:
- `app/Http/Controllers/Api/ImageController.php`
- `app/Services/ImageToolService.php`
- `routes/api.php`
- `public/assets/app.js`

### Text backend

Added backend APIs for:
- text statistics / word counter
- case conversion

Files added and updated:
- `app/Http/Controllers/Api/TextController.php`
- `app/Services/TextToolService.php`
- `routes/api.php`
- `public/assets/app.js`

### Document backend

Added backend processing for:
- PDF to real `.docx` export using extracted page layout, text runs, and OCR fallback
- Word to PDF conversion for `.docx` text extraction and simple `.doc` content
- PDF merge
- PDF split

Files added and updated:
- `app/Http/Controllers/Api/DocumentController.php`
- `app/Http/Controllers/Api/PdfController.php`
- `app/Services/DocumentToolService.php`
- `routes/api.php`
- `public/assets/app.js`

Current limitation:
- exact commercial-grade PDF-to-editable-DOCX fidelity is still not guaranteed for every complex file
- very complex tables, multi-column layouts, floating elements, and rare fonts may still need further refinement

### Download handling

Added temporary download handling so processed files can be downloaded from the browser after API processing.

Files added:
- `app/Http/Controllers/DownloadController.php`
- `app/Services/TemporaryDownloadService.php`

Updated:
- `routes/web.php`

## Packages installed

The following Composer packages were added to support the backend tools:

- `setasign/fpdf`
- `setasign/fpdi`
- `smalot/pdfparser`
- `dompdf/dompdf`
- `phpoffice/phpword`

These packages were added to `composer.json` and installed into `vendor/`.

Additional Python packages were installed for professional PDF parsing and OCR:

- `pymupdf`
- `rapidocr_onnxruntime`
- `Pillow`

These were installed into the local Python environment and are used by the PDF-to-Word conversion backend.

The PDF-to-Word export also uses the existing Composer package:

- `phpoffice/phpword`

And in environments where the PHP `zip` extension is not enabled, the export now falls back to the bundled `PclZip` backend that ships inside `PHPWord`.

## API endpoints added

Additional endpoints now available:

- `POST /api/images/jpg-to-png`
- `POST /api/images/png-to-webp`
- `POST /api/images/compress`
- `POST /api/images/resize`
- `POST /api/text/analyze`
- `POST /api/text/case-convert`

The existing document endpoints were upgraded from scaffold-only handlers to working backend implementations:

- `POST /api/convert/pdf-to-word`
- `POST /api/convert/word-to-pdf`
- `POST /api/pdf/merge`
- `POST /api/pdf/split`

## Database and migrations

No database tables were created for this backend implementation.

This project currently processes files directly through controllers and services and stores temporary output files in:
- `storage/app/temp`

Because no database-backed feature was added, there is no migration required at this stage.

If you later want:
- conversion history
- saved jobs
- queued processing records
- user uploads tracking

then database tables and migrations should be added for those features.

## Verification completed

The backend was tested after implementation.

Verified successfully:
- image conversion endpoints
- image compression endpoint
- image resize endpoint
- text analyze endpoint
- text case conversion endpoint
- PDF to Word endpoint
- Word to PDF endpoint
- PDF merge endpoint
- PDF split endpoint

Also verified:
- `php artisan about`
- `php artisan route:list`
- local app boot on `http://127.0.0.1:8000`

## Additional fixes

### Binary image response corruption fix

An image preview/download corruption issue was fixed after backend integration.

Cause:
- `routes/web.php`
- `routes/api.php`

Both files had leading whitespace before the opening `<?php` tag. That output was being sent before binary image responses and corrupted PNG/WebP file previews and downloads.

Fix applied:
- removed leading whitespace from `routes/web.php`
- removed leading whitespace from `routes/api.php`

Result:
- image preview responses are no longer prefixed with stray bytes
- converted PNG/WebP image files can be rendered correctly by the browser

### Image library change

The image backend now uses `Intervention Image` instead of manual raw GD calls.

Package used:
- `intervention/image`

Files updated:
- `app/Services/ImageToolService.php`
- `composer.json`
- `composer.lock`

### PDF to Word error handling

The PDF-to-Word endpoint was updated so non-text PDFs no longer return a `500 Internal Server Error`.

Cause:
- some uploaded PDFs are scanned/image-only files with no extractable text layer
- the backend text extractor cannot convert those without OCR

Fix applied:
- added `RuntimeException` handling in `app/Http/Controllers/Api/DocumentController.php`
- updated the PDF text extraction error message in `app/Services/DocumentToolService.php`

Result:
- the API now returns a clean `422` JSON response
- the frontend shows a readable error message instead of a generic server error

### PDF to Word professional extraction upgrade

The PDF-to-Word backend was upgraded from basic text extraction to a higher-fidelity conversion pipeline.

What changed:
- text PDFs now use `PyMuPDF` to extract positioned text spans
- font size is preserved more accurately
- bold text is preserved using extracted font metadata
- scanned/image-only PDFs now use OCR fallback with `rapidocr_onnxruntime`
- OCR pages are rendered with page-image preservation

### PDF to Word DOCX formatting enhancement

The PDF-to-Word export was further upgraded from HTML-based `.doc` output to real `.docx` generation.

What changed:
- the export now builds a true Word document with `PHPWord`
- extracted PDF content is grouped into paragraphs, lines, and styled text runs
- bold text is written as real Word bold formatting
- blank lines and paragraph spacing are preserved more cleanly
- OCR/image-only pages are written with the original page image placed behind the OCR text layer
- the export now works even when the PHP `zip` extension is missing by switching `PHPWord` to its `PclZip` backend

Files updated:
- `app/Services/DocumentToolService.php`
- `scripts/pdf_to_word_layout.py`
- `README.md`

Tools and packages used:
- `phpoffice/phpword`
- bundled `PclZip` fallback from `PHPWord`
- `pymupdf`
- `rapidocr_onnxruntime`
- `Pillow`

Notes:
- the paragraph marks and dots between words that may appear in Microsoft Word are Word formatting marks from the editor UI, not literal characters stored in the generated document
- in Word, those can be hidden from the `Home` toolbar by toggling the paragraph-mark display button

Files added and updated:
- `app/Services/DocumentToolService.php`
- `app/Http/Controllers/Api/DocumentController.php`
- `scripts/pdf_to_word_layout.py`
- `README.md`

Tools and packages used:
- Python
- `pymupdf`
- `rapidocr_onnxruntime`
- `Pillow`

Current behavior:
- text PDFs convert to `.docx` using extracted page layout and styled text runs
- scanned PDFs convert using OCR fallback
- OCR pages keep the original page appearance by using page images inside the exported Word document
- blank lines and page flow are preserved better than the earlier HTML-based export

Current limitation:
- exact full-fidelity PDF-to-editable-Word conversion is still not equivalent to a commercial PDF-to-DOCX engine
- very complex tables, columns, floating shapes, and all font styling details may not map perfectly
- OCR quality depends on scan quality and page clarity

### PDF to Word smoothness and fidelity update (April 8, 2026)

This update improves conversion quality to make output more professional and stable.

What was improved:
- improved text run reconstruction so words and spacing are preserved more naturally
- better paragraph detection using line geometry (vertical gaps and indent changes)
- paragraph alignment detection (left, center, right) added to extraction output
- paragraph width and height are now used when placing text boxes in DOCX
- italic and font name metadata are now mapped into DOCX text runs
- overly aggressive whitespace normalization was removed to preserve intended spacing
- OCR path is now resilient: if OCR package is unavailable, conversion still completes with page-image preservation instead of failing hard

Backend hardening:
- Python command invocation now uses escaped shell arguments for safer execution
- configurable Python binary support added via:
   - `config/tools.php` (`tools.document.python_bin`)
   - `.env` override: `PDF_PYTHON_BIN`

Files updated in this update:
- `scripts/pdf_to_word_layout.py`
- `app/Services/DocumentToolService.php`
- `config/tools.php`

Validation done:
- PHP syntax checks passed for updated PHP files
- Python script syntax compile check passed
- runtime dependency check confirmed that the Laragon Python runtime used by `python` command has `fitz` and `rapidocr_onnxruntime` available

### Complete document tools stability + UI upgrade (April 8, 2026)

All document tools were updated to improve reliability, smoother UX flow, and better output quality.

Tools covered:
- PDF to Word
- Word to PDF
- Merge PDF
- Split PDF

Backend improvements:
- Word to PDF now converts DOCX using `PHPWord` HTML export before PDF rendering, instead of only plain-text extraction, which improves formatting retention
- Word to PDF keeps a fallback path for difficult files and returns cleaner user-facing errors
- Merge PDF now validates minimum file count and returns explicit 422 error responses for invalid cases
- Split PDF now validates non-overlapping ranges and reports clear range errors
- Merge/Split endpoints now have `RuntimeException` handling in API controller for clean JSON failures instead of generic server errors

Frontend/UI improvements:
- added live processing status messages (`ready`, `processing`, `success`, `error`) to all four document tool pages
- added busy state for upload dropzones while processing to prevent accidental interactions
- improved split-range UX with inline range validation feedback before API submission
- added professional tool guidance panels with usage tips for each document tool page

Files updated in this release:
- `app/Services/DocumentToolService.php`
- `app/Http/Controllers/Api/PdfController.php`
- `public/assets/app.js`
- `public/assets/app.css`
- `resources/views/tools/documents/pdf-to-word.blade.php`
- `resources/views/tools/documents/word-to-pdf.blade.php`
- `resources/views/tools/documents/pdf-merge.blade.php`
- `resources/views/tools/documents/pdf-split.blade.php`

Validation done:
- PHP syntax checks passed for updated controllers/services
- no Laravel route signature changes were required for existing document tool endpoints

### Document tools pro stabilization release (April 8, 2026 - latest)

This release keeps the existing Laravel + Python hybrid architecture and upgrades weak parts only.

#### What was upgraded

1) Word to PDF (critical fix)
- replaced the low-fidelity DOCX/HTML to Dompdf conversion path with a high-fidelity Python pipeline using LibreOffice headless conversion
- preserves layout, tables, page breaks, spacing, and fonts significantly better for DOCX and DOC
- added environment detection for LibreOffice CLI (`soffice`)
- added clear actionable error when LibreOffice is missing

2) PDF to Word
- kept the existing Python extraction pipeline
- improved operational stability by cleaning temporary extraction directories after conversion
- kept OCR fallback and layout-aware DOCX generation

3) Merge PDF
- keeps existing FPDI merge logic
- improved reliability by catching corrupted/unreadable PDFs with friendly runtime errors

4) Split PDF
- keeps existing FPDI split logic
- improved with overlap range validation and corrupted PDF handling
- improved user-friendly range error behavior

#### UX and smoothness improvements

- document tool pages now show explicit status states: ready, processing, success, error
- upload area is disabled while processing to prevent duplicate submissions
- improved empty state when no files are selected
- improved split-range inline feedback before request submission
- clearer result and guidance cards for each document tool

#### Dependencies and environment updates

- added PHP dependency: `symfony/process`
- added Python dependency file: `requirements.txt`
- added runtime configuration:
   - `PDF_PYTHON_BIN`
   - `LIBREOFFICE_BIN`
   - `WORD_TO_PDF_TIMEOUT`

#### New/updated files in this release

- `app/Services/DocumentToolService.php`
- `scripts/word_to_pdf_libreoffice.py` (new)
- `public/assets/app.js`
- `public/assets/app.css`
- `config/tools.php`
- `.env.example`
- `composer.json`
- `requirements.txt` (new)

#### Validation completed

- PHP syntax checks passed for all updated backend/config files
- Python syntax compile checks passed for:
   - `scripts/pdf_to_word_layout.py`
   - `scripts/word_to_pdf_libreoffice.py`
- Composer autoload confirms `Symfony\\Component\\Process\\Process` is available

#### Windows/Laragon note

- for best Word to PDF quality, install LibreOffice and set:
   - `LIBREOFFICE_BIN=C:\\Program Files\\LibreOffice\\program\\soffice.exe`
- if `LIBREOFFICE_BIN` is empty, the converter attempts auto-detection from PATH and common Windows install locations

### Word-to-PDF LibreOffice runtime fix (April 8, 2026 - follow-up)

After enabling LibreOffice conversion, some environments returned:
- `Could not find platform independent libraries <prefix>`
- `SfxBaseModel::impl_store ... Error Area:Io Class:Write Code:16`

Root cause:
- the Python process runner was overriding environment variables too aggressively, which could remove required Windows/runtime variables used by LibreOffice (`PATH`, `TEMP`, `SYSTEMROOT`, etc.).

Fix applied:
- updated `app/Services/DocumentToolService.php` so Python subprocesses now inherit the full system environment and only override `PYTHONHASHSEED=0`.

Result:
- keeps the Python startup-stability workaround
- avoids breaking LibreOffice runtime dependencies during DOC/DOCX to PDF conversion

### UI/UX + SEO upgrade (April 8, 2026)

This release improves the full frontend experience to feel smoother and more professional.

What changed:
- refreshed branding to `Covertsanything`
- upgraded home page content with stronger value messaging
- added returning-visitor resume card and auto-open behavior for the last used tool
- added global conversion loader overlay during file processing
- improved visual design with better typography, spacing, gradients, and responsive polish
- removed outdated category notice text that no longer matched backend reality

SEO improvements:
- dynamic meta title and meta description per page (home/category/tool)
- Open Graph tags (`og:title`, `og:description`, `og:url`, `og:type`)
- Twitter card meta tag
- canonical URL tag
- robots index/follow tag

Files updated:
- `app/Http/Controllers/PageController.php`
- `resources/views/layouts/app.blade.php`
- `resources/views/pages/home.blade.php`
- `resources/views/pages/category.blade.php`
- `resources/views/partials/header.blade.php`
- `resources/views/partials/footer.blade.php`
- `public/assets/app.js`
- `public/assets/app.css`
- `config/tools.php`

Conversion stack summary:

Word to PDF:
- uses local Python script `scripts/word_to_pdf_libreoffice.py`
- converter engine is LibreOffice headless (`soffice`)
- orchestrated by Laravel service: `app/Services/DocumentToolService.php`
- key PHP package for robust process execution: `symfony/process`
- no third-party cloud API is used

PDF to Word:
- uses local Python script `scripts/pdf_to_word_layout.py`
- extraction engine: `PyMuPDF` (`fitz`)
- OCR fallback: `rapidocr_onnxruntime`
- image support: `Pillow`
- DOCX generation is done server-side in Laravel with `phpoffice/phpword`
- no third-party cloud API is used

### Image tools polish update (April 8, 2026)

This release improves the image section UX and adds a universal image converter.

What changed:
- added a new `Image Converter` card for any common image upload format
- added output format selection for JPEG, PNG, and WebP
- added file size output labels on conversion results for JPG to PNG, PNG to WebP, Compressor, Resizer, and the new Image Converter
- added visible processing status banners for image tools
- added copy-to-clipboard confirmation feedback for text tools
- removed home-page auto-redirect to the last used tool
- switched the UI font family to `Poppins`
- updated the palette to keep the site in teal + amber tones without red accents

New image API:
- `POST /api/images/convert`

Files updated in this release:
- `app/Services/ImageToolService.php`
- `app/Http/Controllers/Api/ImageController.php`
- `routes/api.php`
- `config/tools.php`
- `public/assets/app.js`
- `public/assets/app.css`
- `resources/views/tools/images/image-converter.blade.php`
- `resources/views/tools/images/jpg-to-png.blade.php`
- `resources/views/tools/images/png-to-webp.blade.php`
- `resources/views/tools/images/compressor.blade.php`
- `resources/views/tools/images/resizer.blade.php`

Validation done:
- updated PHP files passed syntax checks
- updated Blade and JavaScript files passed workspace diagnostics

## Project features included

### Image tools

These run locally in the browser using canvas-based processing from `public/assets/app.js`.

1. **JPG to PNG**
   - accepts `.jpg` and `.jpeg`
   - converts to PNG locally
   - supports multiple files

2. **PNG to WebP**
   - accepts `.png`
   - converts to WebP locally
   - quality slider included
   - supports multiple files

3. **Image Compressor**
   - accepts JPG, PNG, WebP
   - output quality slider included
   - optional output format selector
   - compresses locally

4. **Image Resizer**
   - accepts JPG, PNG, WebP
   - width and height controls
   - optional aspect ratio lock
   - resizes locally

### Document tools

These are wired to Laravel API endpoints.

1. **PDF to Word**
   - frontend upload ready
   - posts to `POST /api/convert/pdf-to-word`
   - backend implemented
   - exports real `.docx`
   - uses OCR fallback for image-only PDFs

2. **Word to PDF**
   - frontend upload ready
   - posts to `POST /api/convert/word-to-pdf`
   - backend implemented

3. **PDF Merge**
   - frontend upload ready
   - supports file ordering
   - posts to `POST /api/pdf/merge`
   - backend implemented

4. **PDF Split**
   - frontend upload ready
   - page-range UI included
   - posts to `POST /api/pdf/split`
   - backend implemented

### Text tools

These run locally in the browser.

1. **Word Counter**
   - word count
   - character count
   - no-space count
   - sentence count
   - paragraph count
   - reading time
   - speaking time

2. **Case Converter**
   - uppercase
   - lowercase
   - sentence case
   - title case
   - capitalized
   - alternating case
   - inverse case

## Folder structure

```text
cnproject/
|-- app/
|   `-- Http/
|       `-- Controllers/
|           |-- Controller.php
|           |-- PageController.php
|           `-- Api/
|               |-- DocumentController.php
|               `-- PdfController.php
|-- bootstrap/
|   |-- app.php
|   |-- providers.php
|   `-- cache/
|-- config/
|   `-- tools.php
|-- public/
|   |-- index.php
|   `-- assets/
|       |-- app.css
|       `-- app.js
|-- resources/
|   `-- views/
|-- routes/
|   |-- api.php
|   |-- console.php
|   `-- web.php
|-- storage/
|   |-- app/
|   |-- framework/
|   `-- logs/
|-- artisan
|-- composer.json
|-- .env
|-- .env.example
`-- README.md
```

## How routing works

### Web routes

Defined in `routes/web.php`:

- `/` -> homepage
- `/tools/images` -> image tools category page
- `/tools/documents` -> document tools category page
- `/tools/text` -> text tools category page
- `/tools/{category}/{tool}` -> single tool page

### API routes

Defined in `routes/api.php`:

- `POST /api/convert/pdf-to-word`
- `POST /api/convert/word-to-pdf`
- `POST /api/pdf/merge`
- `POST /api/pdf/split`

## How the app works internally

### 1. Config-driven tool data

All category/tool metadata lives in `config/tools.php`.

This file stores:
- brand and homepage content
- category names
- descriptions
- tool slugs
- Blade view names
- route/back-link metadata

### 2. PageController

`app/Http/Controllers/PageController.php` is the main frontend controller.

It provides three actions:
- `home()` for the homepage
- `category($category)` for category pages
- `tool($category, $tool)` for single tool pages

### 3. Blade layout system

`resources/views/layouts/app.blade.php` is the main layout.

It injects:
- CSS file
- JS file
- CSRF token
- API base URL
- shared header/footer

### 4. Plain JS tool engine

`public/assets/app.js` contains the browser-side logic.

It handles:
- file selection
- drag and drop
- file validation
- image conversion/compression/resizing
- word counter calculations
- case transformations
- document tool API submission

### 5. Plain CSS theme

`public/assets/app.css` provides the dashboard and tool styling without requiring Tailwind or a frontend build tool.

## Local setup

This repository can now be run directly.

### Step 1

Install dependencies if needed.

```bash
composer install
```

### Step 2

Create your environment file if `.env` does not already exist.

```bash
cp .env.example .env
```

Windows PowerShell:

```powershell
copy .env.example .env
```

### Step 3

Generate the application key.

```bash
php artisan key:generate
```

### Step 4

Serve locally.

```bash
php artisan serve
```

Then open:

```text
http://127.0.0.1:8000
```

### Optional

Create a storage symlink if future document converters will save files in `storage/app/public`.

```bash
php artisan storage:link
```

## What works immediately after local setup

After running `php artisan serve` in this repository:

### Fully working immediately

- homepage
- category pages
- Blade UI layout
- JPG to PNG
- PNG to WebP
- image compressor
- image resizer
- word counter
- case converter
- PDF to Word
- Word to PDF
- PDF Merge
- PDF Split

## Why document tool output can still vary

The document backend is implemented, but PDF-to-Word is still a difficult class of conversion problem.

The heavy work is now handled server-side with:
- `setasign/fpdi`
- `dompdf/dompdf`
- `phpoffice/phpword`
- `pymupdf`
- `rapidocr_onnxruntime`

Very high-fidelity PDF-to-editable-Word conversion can still vary depending on:
- table complexity
- scan quality
- multi-column layouts
- custom fonts
- floating graphics

## Example backend implementation direction

### PDF Merge

Use `setasign/fpdi` to import pages from multiple PDFs and output one merged PDF.

### PDF Split

Use `setasign/fpdi` to extract the requested page ranges and save each part, then zip them.

### Word to PDF

Common approach:
- upload `.doc/.docx`
- convert using LibreOffice in headless mode
- return the generated PDF path

### PDF to Word

Current approach in this project:
- use `PyMuPDF` to extract layout/text
- use OCR for scanned pages
- rebuild content into a real `.docx` with `PHPWord`
- preserve page images for OCR pages

## Validation rules already included

### DocumentController

- PDF to Word requires `pdf`
- Word to PDF requires `doc` or `docx`
- file max size: 50MB

### PdfController

- merge requires `files[]` with at least 2 PDFs
- split requires one PDF plus `ranges`
- file max size: 50MB

## How each page maps from the original project

### Original Next.js structure

- `app/page.tsx`
- `app/tools/.../page.tsx`
- `components/...`
- `lib/api.ts`

### Converted Laravel structure

- `resources/views/pages/home.blade.php`
- `resources/views/tools/.../*.blade.php`
- `resources/views/partials/*.blade.php`
- `public/assets/app.js`
- `app/Http/Controllers/*.php`

## Notes about styling changes

The general product feel was preserved:
- soft shadows
- rounded cards
- hero and category sections
- card-based tools
- professional SaaS-style layout

The UI was rewritten in plain CSS so it can run directly in Laravel without the original React/Tailwind component system.

## Missing pieces from the original React version

These React-specific pieces were intentionally not carried over 1:1:
- Lucide React icons
- ShadCN components
- Next.js `Link`
- React state/hooks
- TypeScript interfaces
- Tailwind utility classes

They were replaced with Blade, PHP config, and vanilla JavaScript equivalents.

## If you want to continue this conversion further

The next useful upgrades would be:
1. convert CSS to Laravel + Vite + Tailwind again
2. add real PDF/Word libraries
3. create download-history storage
4. add ZIP batch download for image tools
5. save uploads temporarily in `storage/app/temp`
6. add jobs/queues for large file processing
7. add file cleanup scheduler

## Suggested packages for backend completion

```bash
composer require setasign/fpdi tecnickcom/tcpdf phpoffice/phpword
```

If you use LibreOffice on the server, install it on the machine and call it from Laravel using Symfony Process.

## Final summary

This project now gives you:
- Laravel Blade pages instead of `.tsx`
- Laravel route/controller structure
- restored Laravel bootstrap files
- working local image/text tools
- document tool frontend integrated to Laravel APIs
- a README that reflects the runnable local setup

What it still does not include is the real server-side PDF/Word conversion engine.
