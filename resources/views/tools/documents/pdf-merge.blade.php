@extends('layouts.app')

@section('content')
@include('partials.tool-layout-start')

<div class="tool-app" data-tool="pdf-merge">
    <div class="dropzone" data-dropzone data-accept=".pdf,application/pdf" data-multiple="true" data-max-size="52428800">
        <input type="file" accept=".pdf,application/pdf" multiple>
        <div class="dropzone-icon">⬆️</div>
        <p>Drop multiple PDF files here or click to upload</p>
        <small>Reorder files before sending them to Laravel.</small>
    </div>
    <div class="message status" data-status>Ready. Add at least two PDFs to merge.</div>
    <div class="message error" data-error hidden></div>
    <div class="file-list reorder" data-file-list></div>
    <div class="action-row"><button class="button primary" data-submit disabled>Merge PDFs</button></div>
    <div class="results" data-results></div>
    <div class="tool-notes">
        <h4>Tips for smooth merging</h4>
        <ul>
            <li>Use the Up/Down buttons to reorder files before merging.</li>
            <li>Make sure each file is a valid and readable PDF.</li>
            <li>Keep total upload size manageable for faster processing.</li>
        </ul>
    </div>
    <div class="endpoint-card"><strong>Laravel API Endpoint</strong><code>POST /api/pdf/merge</code></div>
</div>

@include('partials.tool-layout-end')
@endsection
