@extends('layouts.app')

@section('content')
@include('partials.tool-layout-start')

<div class="tool-app" data-tool="image-converter">
    <div class="dropzone" data-dropzone data-hide-on-files="true" data-accept="image/jpeg,image/png,image/webp,image/gif,image/bmp,image/tiff" data-multiple="true" data-max-size="52428800">
        <input type="file" accept="image/jpeg,image/png,image/webp,image/gif,image/bmp,image/tiff" multiple>
        <div class="dropzone-icon">⬆️</div>
        <p>Drop any supported image here or click to upload</p>
        <small>Convert JPG, PNG, WebP, GIF, BMP, or TIFF to your chosen format.</small>
    </div>
    <div class="message status" data-status>Ready. Upload your file and start.</div>
    <div class="option-bar">
        <label>Output quality
            <input type="range" min="20" max="100" value="85" data-quality-range>
        </label>
        <strong data-quality-value>85%</strong>
    </div>
    <div class="option-grid">
        <label>Output format
            <select data-convert-format>
                <option value="image/jpeg">JPEG</option>
                <option value="image/png">PNG</option>
                <option value="image/webp" selected>WebP</option>
            </select>
        </label>
    </div>
    <div class="action-row action-row left">
        <small class="help" data-estimated-size>Estimated output size: -</small>
    </div>
    <div class="message error" data-error hidden></div>
    <div class="file-list" data-file-list></div>
    <div class="action-row"><button class="button primary" data-convert disabled>Convert Images</button></div>
    <div class="results" data-results></div>
    <div class="tool-notes">
        <h4>Useful for</h4>
        <ul>
            <li>Converting mixed image uploads into one output format.</li>
            <li>Choosing WebP or JPEG for smaller file sizes.</li>
            <li>Keeping a simple, single-step conversion workflow.</li>
        </ul>
    </div>
    <div class="endpoint-card"><strong>Laravel API Endpoint</strong><code>POST /api/images/convert</code></div>
</div>

@include('partials.tool-layout-end')
@endsection