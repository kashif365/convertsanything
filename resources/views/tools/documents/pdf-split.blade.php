@extends('layouts.app')

@section('content')
@include('partials.tool-layout-start')

<div class="tool-app" data-tool="pdf-split">
    <div class="dropzone" data-dropzone data-accept=".pdf,application/pdf" data-multiple="false" data-max-size="52428800">
        <input type="file" accept=".pdf,application/pdf">
        <div class="dropzone-icon">⬆️</div>
        <p>Drop a PDF file here or click to upload</p>
        <small>Uploaded to Laravel API: POST /api/pdf/split</small>
    </div>
    <div class="range-panel">
        <div class="range-head">
            <h3>Page Ranges</h3>
            <button class="button secondary small" type="button" data-add-range>Add Range</button>
        </div>
        <div data-ranges></div>
        <p class="help">Example: 1 to 5, 10 to 15. Each range should become one split output.</p>
    </div>
    <div class="message status" data-status>Ready. Add ranges and start splitting.</div>
    <div class="message error" data-error hidden></div>
    <div class="file-list" data-file-list></div>
    <div class="action-row"><button class="button primary" data-submit disabled>Split PDF</button></div>
    <div class="results" data-results></div>
    <div class="tool-notes">
        <h4>Tips for cleaner split results</h4>
        <ul>
            <li>Use non-overlapping ranges like 1-3 and 4-8.</li>
            <li>Start page must be at least 1 and end page must be equal or greater.</li>
            <li>Each range produces a separate output PDF.</li>
        </ul>
    </div>
    <div class="endpoint-card"><strong>Laravel API Endpoint</strong><code>POST /api/pdf/split</code></div>
</div>

@include('partials.tool-layout-end')
@endsection
