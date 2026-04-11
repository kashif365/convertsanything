@extends('layouts.app')

@section('content')
@include('partials.tool-layout-start')

<div class="tool-app" data-tool="resizer">
    <div class="dropzone" data-dropzone data-hide-on-files="true" data-accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-multiple="true" data-max-size="52428800">
        <input type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple>
        <div class="dropzone-icon">⬆️</div>
        <p>Drop images here or click to upload</p>
        <small>Resize to exact dimensions. Maximum file size: 50MB.</small>
    </div>
    <div class="option-bar">
        <label>Output quality
            <input type="range" min="20" max="100" value="85" data-quality-range>
        </label>
        <strong data-quality-value>85%</strong>
    </div>
    <div class="option-grid three">
        <label>Width (px)
            <input type="number" value="1200" min="1" data-width>
        </label>
        <label>Height (px)
            <input type="number" value="800" min="1" data-height>
        </label>
        <label class="checkbox-label">
            <input type="checkbox" checked data-lock-aspect>
            Lock aspect ratio
        </label>
    </div>
    <div class="message status" data-status>Ready. Upload your file and start.</div>
    <div class="action-row action-row left">
        <small class="help" data-estimated-size>Estimated output size: -</small>
    </div>
    <div class="message error" data-error hidden></div>
    <div class="file-list" data-file-list></div>
    <div class="action-row"><button class="button primary" data-convert disabled>Resize Images</button></div>
    <div class="results" data-results></div>
    <div class="tool-notes">
        <h4>Tip</h4>
        <ul>
            <li>Output size is shown after resizing completes.</li>
            <li>Lock aspect ratio for consistent image proportions.</li>
        </ul>
    </div>
</div>

@include('partials.tool-layout-end')
@endsection
