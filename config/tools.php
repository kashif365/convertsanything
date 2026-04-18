<?php
return [
    'brand'   => 'Covertsanything',
    'tagline' => 'Free online tools for PDF, Word, image, and text conversion. No signup. No storage.',

    'document' => [
        'libreoffice_bin'         => env('LIBREOFFICE_BIN', ''),
        'word_to_pdf_timeout'     => (int) env('WORD_TO_PDF_TIMEOUT', 180),
        'word_to_pdf_api_enabled' => (bool) env('WORD_TO_PDF_API_ENABLED', false),
        'convertapi_secret'       => env('CONVERTAPI_SECRET', ''),
        'word_to_pdf_api_timeout' => (int) env('WORD_TO_PDF_API_TIMEOUT', 120),
        'pdf_to_word_timeout'     => (int) env('PDF_TO_WORD_TIMEOUT', 240),
        'tesseract_bin'           => env('TESSERACT_BIN', ''),
        'pdftoppm_bin'            => env('PDFTOPPM_BIN', ''),
    ],

    'ocr' => [
        'api_key' => env('OCR_SPACE_API_KEY', 'helloworld'),
    ],

    'features' => [
        ['title' => 'Instant Processing',  'description' => 'Files are processed immediately on the server — no queue, no wait.',  'emoji' => '⚡'],
        ['title' => 'No Data Retention',   'description' => 'Uploaded files are deleted right after processing. Nothing is stored.', 'emoji' => '🛡️'],
        ['title' => 'No Account Needed',   'description' => 'Open the tool, upload, download. No signup, no tracking.',              'emoji' => '🌐'],
    ],

    'categories' => [
        'images' => [
            'title'       => 'Image Tools',
            'description' => 'Convert, compress, resize, and extract text from images.',
            'emoji'       => '🖼️',
            'variant'     => 'default',
            'tools'       => [
                ['slug' => 'jpg-to-png',      'title' => 'JPG to PNG',       'description' => 'Convert JPG images to lossless PNG format.'],
                ['slug' => 'png-to-webp',     'title' => 'PNG to WebP',      'description' => 'Convert PNG to WebP for smaller file sizes and better web performance.'],
                ['slug' => 'image-converter', 'title' => 'Image Converter',  'description' => 'Convert any common image format to JPEG, PNG, or WebP.'],
                ['slug' => 'compressor',      'title' => 'Image Compressor', 'description' => 'Reduce image file size while maintaining good visual quality.'],
                ['slug' => 'resizer',         'title' => 'Image Resizer',    'description' => 'Resize images by percentage or exact pixel dimensions.'],
                ['slug' => 'img-to-text',     'title' => 'Image to Text',    'description' => 'Extract text from images using OCR. Supports 11 languages.'],
            ],
        ],
        'documents' => [
            'title'       => 'Document Tools',
            'description' => 'Convert and manipulate PDF and Word documents server-side.',
            'emoji'       => '📄',
            'variant'     => 'accent',
            'tools'       => [
                ['slug' => 'pdf-to-word', 'title' => 'PDF to Word', 'description' => 'Convert PDF files into editable Word documents.'],
                ['slug' => 'word-to-pdf', 'title' => 'Word to PDF', 'description' => 'Convert DOC or DOCX files to universally compatible PDFs.'],
                ['slug' => 'pdf-merge',   'title' => 'Merge PDF',   'description' => 'Combine multiple PDF files into one document.'],
                ['slug' => 'pdf-split',   'title' => 'Split PDF',   'description' => 'Extract pages or split a PDF by page ranges.'],
            ],
        ],
        'text' => [
            'title'       => 'Text Tools',
            'description' => 'Analyze and transform text directly in your browser.',
            'emoji'       => '🔤',
            'variant'     => 'default',
            'tools'       => [
                ['slug' => 'word-counter',   'title' => 'Word Counter',   'description' => 'Count words, characters, sentences, and estimate reading time.'],
                ['slug' => 'case-converter', 'title' => 'Case Converter', 'description' => 'Transform text between uppercase, lowercase, title case, and more.'],
            ],
        ],
    ],

    'tool_pages' => [
        'jpg-to-png' => [
            'category' => 'images', 'title' => 'JPG to PNG',
            'description' => 'Convert JPG images to PNG with lossless quality. Optionally scale dimensions before converting.',
            'backHref' => '/tools/images', 'backLabel' => 'Image Tools', 'view' => 'tools.images.jpg-to-png',
        ],
        'png-to-webp' => [
            'category' => 'images', 'title' => 'PNG to WebP',
            'description' => 'Convert PNG images to WebP for superior compression. Control quality and output scale.',
            'backHref' => '/tools/images', 'backLabel' => 'Image Tools', 'view' => 'tools.images.png-to-webp',
        ],
        'image-converter' => [
            'category' => 'images', 'title' => 'Image Converter',
            'description' => 'Convert JPG, PNG, WebP, GIF, BMP, or TIFF to your chosen output format with quality and scale control.',
            'backHref' => '/tools/images', 'backLabel' => 'Image Tools', 'view' => 'tools.images.image-converter',
        ],
        'compressor' => [
            'category' => 'images', 'title' => 'Image Compressor',
            'description' => 'Compress JPG, PNG, and WebP images. Adjust quality and scale to hit your target file size.',
            'backHref' => '/tools/images', 'backLabel' => 'Image Tools', 'view' => 'tools.images.compressor',
        ],
        'resizer' => [
            'category' => 'images', 'title' => 'Image Resizer',
            'description' => 'Resize images by percentage scale or exact pixel dimensions with optional aspect ratio lock.',
            'backHref' => '/tools/images', 'backLabel' => 'Image Tools', 'view' => 'tools.images.resizer',
        ],
        'img-to-text' => [
            'category' => 'images', 'title' => 'Image to Text',
            'description' => 'Upload an image and extract readable text using OCR. Supports English, Spanish, French, German, and more.',
            'backHref' => '/tools/images', 'backLabel' => 'Image Tools', 'view' => 'tools.images.img-to-text',
        ],
        'pdf-to-word' => [
            'category' => 'documents', 'title' => 'PDF to Word',
            'description' => 'Convert a PDF to an editable Word document using LibreOffice server-side processing.',
            'backHref' => '/tools/documents', 'backLabel' => 'Document Tools', 'view' => 'tools.documents.pdf-to-word',
        ],
        'word-to-pdf' => [
            'category' => 'documents', 'title' => 'Word to PDF',
            'description' => 'Convert DOC or DOCX files to PDF using LibreOffice headless conversion.',
            'backHref' => '/tools/documents', 'backLabel' => 'Document Tools', 'view' => 'tools.documents.word-to-pdf',
        ],
        'pdf-merge' => [
            'category' => 'documents', 'title' => 'Merge PDF',
            'description' => 'Upload multiple PDFs and combine them into a single document. Drag to reorder before merging.',
            'backHref' => '/tools/documents', 'backLabel' => 'Document Tools', 'view' => 'tools.documents.pdf-merge',
        ],
        'pdf-split' => [
            'category' => 'documents', 'title' => 'Split PDF',
            'description' => 'Split a PDF into multiple files by specifying page ranges.',
            'backHref' => '/tools/documents', 'backLabel' => 'Document Tools', 'view' => 'tools.documents.pdf-split',
        ],
        'word-counter' => [
            'category' => 'text', 'title' => 'Word Counter',
            'description' => 'Analyze text: word count, character count, sentences, paragraphs, and reading time.',
            'backHref' => '/tools/text', 'backLabel' => 'Text Tools', 'view' => 'tools.text.word-counter',
        ],
        'case-converter' => [
            'category' => 'text', 'title' => 'Case Converter',
            'description' => 'Convert text between uppercase, lowercase, sentence case, title case, and more.',
            'backHref' => '/tools/text', 'backLabel' => 'Text Tools', 'view' => 'tools.text.case-converter',
        ],
    ],
];
