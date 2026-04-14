<?php
return [
    'brand' => 'Covertsanything',
    'tagline' => 'Professional file tools for PDF, Word, image, and text conversion. Smooth experience, accurate output, and fast processing.',
    'document' => [
        'libreoffice_bin' => env('LIBREOFFICE_BIN', ''),
        'word_to_pdf_timeout' => (int) env('WORD_TO_PDF_TIMEOUT', 180),
        'word_to_pdf_api_enabled' => (bool) env('WORD_TO_PDF_API_ENABLED', false),
        'convertapi_secret' => env('CONVERTAPI_SECRET', ''),
        'word_to_pdf_api_timeout' => (int) env('WORD_TO_PDF_API_TIMEOUT', 120),
        'pdf_to_word_timeout' => (int) env('PDF_TO_WORD_TIMEOUT', 240),
        'tesseract_bin' => env('TESSERACT_BIN', ''),
        'pdftoppm_bin' => env('PDFTOPPM_BIN', ''),
    ],
    'features' => [
        ['title' => 'Instant Processing', 'description' => 'Most tools run directly in your browser for immediate results.', 'emoji' => '⚡'],
        ['title' => 'Privacy First', 'description' => 'Your files are never stored. Processing happens locally when possible.', 'emoji' => '🛡️'],
        ['title' => 'Universal Access', 'description' => 'Works on any device with a modern browser. No installation needed.', 'emoji' => '🌐'],
    ],
    'categories' => [
        'images' => [
            'title' => 'Image Tools',
            'description' => 'Convert and optimize images instantly in your browser. All processing happens locally for maximum privacy.',
            'emoji' => '🖼️',
            'variant' => 'default',
            'tools' => [
                ['slug' => 'jpg-to-png', 'title' => 'JPG to PNG', 'description' => 'Convert JPG images to PNG format with lossless quality preservation.', 'emoji' => '🔄'],
                ['slug' => 'png-to-webp', 'title' => 'PNG to WebP', 'description' => 'Convert PNG to WebP for superior compression and web performance.', 'emoji' => '🧩'],
                ['slug' => 'image-converter', 'title' => 'Image Converter', 'description' => 'Upload any common image format and choose the output format, size, and quality.', 'emoji' => '🪄'],
                ['slug' => 'compressor', 'title' => 'Image Compressor', 'description' => 'Reduce file size while maintaining optimal visual quality.', 'emoji' => '🗜️'],
                ['slug' => 'resizer', 'title' => 'Image Resizer', 'description' => 'Resize images to exact dimensions with aspect ratio control.', 'emoji' => '📐'],
            ],
        ],
        'documents' => [
            'title' => 'Document Tools',
            'description' => 'Process PDFs and Word documents securely with your Laravel backend APIs.',
            'emoji' => '📄',
            'variant' => 'accent',
            'tools' => [
                ['slug' => 'pdf-to-word', 'title' => 'PDF to Word', 'description' => 'Extract content from PDFs into fully editable Word documents.', 'emoji' => '⬇️'],
                ['slug' => 'word-to-pdf', 'title' => 'Word to PDF', 'description' => 'Convert Word documents to universally compatible PDF files.', 'emoji' => '⬆️'],
                ['slug' => 'pdf-merge', 'title' => 'Merge PDF', 'description' => 'Combine multiple PDF files into a single organized document.', 'emoji' => '🧷'],
                ['slug' => 'pdf-split', 'title' => 'Split PDF', 'description' => 'Extract specific pages or split PDFs by page ranges.', 'emoji' => '✂️'],
            ],
        ],
        'text' => [
            'title' => 'Text Tools',
            'description' => 'Analyze and transform text instantly. All processing happens in your browser for maximum privacy.',
            'emoji' => '🔤',
            'variant' => 'default',
            'tools' => [
                ['slug' => 'word-counter', 'title' => 'Word Counter', 'description' => 'Analyze text with word, character, and sentence statistics.', 'emoji' => '🔢'],
                ['slug' => 'case-converter', 'title' => 'Case Converter', 'description' => 'Transform text between uppercase, lowercase, and title case.', 'emoji' => '🔠'],
            ],
        ],
    ],
    'tool_pages' => [
        'jpg-to-png' => [
            'category' => 'images', 'title' => 'JPG to PNG', 'description' => 'Convert JPG/JPEG images to PNG format with lossless quality. All processing happens locally in your browser.',
            'backHref' => '/tools/images', 'backLabel' => 'Back to Image Tools', 'view' => 'tools.images.jpg-to-png',
        ],
        'png-to-webp' => [
            'category' => 'images', 'title' => 'PNG to WebP', 'description' => 'Convert PNG images to WebP for smaller file sizes and better web performance.',
            'backHref' => '/tools/images', 'backLabel' => 'Back to Image Tools', 'view' => 'tools.images.png-to-webp',
        ],
        'image-converter' => [
            'category' => 'images', 'title' => 'Image Converter', 'description' => 'Upload JPG, PNG, WebP, GIF, BMP, or TIFF files and convert them to PNG, JPEG, or WebP with quality control.',
            'backHref' => '/tools/images', 'backLabel' => 'Back to Image Tools', 'view' => 'tools.images.image-converter',
        ],
        'compressor' => [
            'category' => 'images', 'title' => 'Image Compressor', 'description' => 'Compress JPG, JPEG, PNG, and WebP images directly in your browser while keeping good visual quality.',
            'backHref' => '/tools/images', 'backLabel' => 'Back to Image Tools', 'view' => 'tools.images.compressor',
        ],
        'resizer' => [
            'category' => 'images', 'title' => 'Image Resizer', 'description' => 'Resize images to exact width and height with optional aspect ratio lock. Processing happens locally.',
            'backHref' => '/tools/images', 'backLabel' => 'Back to Image Tools', 'view' => 'tools.images.resizer',
        ],
        'pdf-to-word' => [
            'category' => 'documents', 'title' => 'PDF to Word', 'description' => 'Upload a PDF and send it to your Laravel backend for conversion into an editable Word document.',
            'backHref' => '/tools/documents', 'backLabel' => 'Back to Document Tools', 'view' => 'tools.documents.pdf-to-word',
        ],
        'word-to-pdf' => [
            'category' => 'documents', 'title' => 'Word to PDF', 'description' => 'Upload a DOC or DOCX file and send it to your Laravel backend for conversion into a PDF.',
            'backHref' => '/tools/documents', 'backLabel' => 'Back to Document Tools', 'view' => 'tools.documents.word-to-pdf',
        ],
        'pdf-merge' => [
            'category' => 'documents', 'title' => 'PDF Merge', 'description' => 'Combine multiple PDF files into a single PDF document. Reorder files before sending them to Laravel.',
            'backHref' => '/tools/documents', 'backLabel' => 'Back to Document Tools', 'view' => 'tools.documents.pdf-merge',
        ],
        'pdf-split' => [
            'category' => 'documents', 'title' => 'PDF Split', 'description' => 'Split a PDF into multiple smaller PDFs by specifying page ranges. Laravel handles the server-side processing.',
            'backHref' => '/tools/documents', 'backLabel' => 'Back to Document Tools', 'view' => 'tools.documents.pdf-split',
        ],
        'word-counter' => [
            'category' => 'text', 'title' => 'Word Counter', 'description' => 'Analyze text with word, character, sentence, paragraph, reading time, and speaking time statistics.',
            'backHref' => '/tools/text', 'backLabel' => 'Back to Text Tools', 'view' => 'tools.text.word-counter',
        ],
        'case-converter' => [
            'category' => 'text', 'title' => 'Case Converter', 'description' => 'Transform text between uppercase, lowercase, sentence case, title case, inverse case, and more.',
            'backHref' => '/tools/text', 'backLabel' => 'Back to Text Tools', 'view' => 'tools.text.case-converter',
        ],
    ],
];
