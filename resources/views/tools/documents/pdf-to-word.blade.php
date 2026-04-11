@extends('layouts.app')

@section('content')
@include('partials.tool-layout-start')

<div class="tool-app" data-tool="pdf-to-word">
    <div class="dropzone" data-dropzone data-accept=".pdf,application/pdf" data-multiple="false" data-max-size="52428800">
        <input type="file" accept=".pdf,application/pdf">
        <div class="dropzone-icon">⬆️</div>
        <p>Drop a PDF here or click to upload</p>
        <small>Uploaded to Laravel API: POST /api/convert/pdf-to-word</small>
    </div>
    <div class="message status" data-status>Ready. Upload your file and start.</div>
    <div class="message error" data-error hidden></div>
    <div class="file-list" data-file-list></div>
    <div class="action-row"><button class="button primary" data-submit disabled>Convert PDF to Word</button></div>
    <div class="results" data-results></div>
    <div class="tool-notes">
        <h4>Tips for better conversion quality</h4>
        <ul>
            <li>Use clear PDFs without security restrictions.</li>
            <li>Text-based PDFs convert best into editable DOCX.</li>
            <li>Scanned PDFs use OCR and may need minor manual cleanup.</li>
        </ul>
    </div>
    <div class="endpoint-card"><strong>Laravel API Endpoint</strong><code>POST /api/convert/pdf-to-word</code></div>
</div>

@include('partials.tool-layout-end')
@endsection
