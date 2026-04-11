@extends('layouts.app')

@section('content')
@include('partials.tool-layout-start')

<div class="tool-app" data-tool="word-to-pdf">
    <div class="dropzone" data-dropzone data-accept=".doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" data-multiple="false" data-max-size="52428800">
        <input type="file" accept=".doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
        <div class="dropzone-icon">⬆️</div>
        <p>Drop a Word document here or click to upload</p>
        <small>Uploaded to Laravel API: POST /api/convert/word-to-pdf</small>
    </div>
    <div class="message status" data-status>Ready. Upload your file and start.</div>
    <div class="message error" data-error hidden></div>
    <div class="file-list" data-file-list></div>
    <div class="action-row"><button class="button primary" data-submit disabled>Convert Word to PDF</button></div>
    <div class="results" data-results></div>
    <div class="tool-notes">
        <h4>Tips for better PDF output</h4>
        <ul>
            <li>DOCX files preserve formatting better than legacy DOC files.</li>
            <li>Embedded fonts/images in the source document improve output consistency.</li>
            <li>If layout is complex, review the generated PDF before sharing.</li>
        </ul>
    </div>
    <div class="endpoint-card"><strong>Laravel API Endpoint</strong><code>POST /api/convert/word-to-pdf</code></div>
</div>

@include('partials.tool-layout-end')
@endsection
