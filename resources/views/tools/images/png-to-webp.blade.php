@extends('layouts.app')

@section('content')
@include('partials.tool-layout-start')

<div class="tool-app" data-tool="png-to-webp">
    <div class="dropzone" data-dropzone data-hide-on-files="true" data-accept=".png,image/png" data-multiple="true" data-max-size="52428800">
        <input type="file" accept=".png,image/png" multiple>
        <div class="dropzone-icon">⬆️</div>
        <p>Drop PNG images here or click to upload</p>
        <small>Maximum file size: 50MB</small>
    </div>
    <div class="option-bar">
        <label>WebP quality
            <input type="range" min="50" max="100" value="90" data-quality-range>
        </label>
        <strong data-quality-value>90%</strong>
    </div>
    <div class="action-row action-row left">
        <small class="help" data-estimated-size>Estimated output size: -</small>
    </div>
    <div class="message status" data-status>Ready. Upload your file and start.</div>
    <div class="message error" data-error hidden></div>
    <div class="file-list" data-file-list></div>
    <div class="action-row"><button class="button primary" data-convert disabled>Convert to WebP</button></div>
    <div class="results" data-results></div>
    <div class="tool-notes">
        <h4>Tip</h4>
        <ul>
            <li>WebP usually reduces file size more than PNG.</li>
            <li>Output size is shown after the conversion completes.</li>
        </ul>
    </div>
</div>

@include('partials.tool-layout-end')
@endsection
