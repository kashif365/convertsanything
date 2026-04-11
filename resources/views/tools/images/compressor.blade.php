@extends('layouts.app')

@section('content')
@include('partials.tool-layout-start')

<div class="tool-app" data-tool="compressor">
    <div class="dropzone" data-dropzone data-hide-on-files="true" data-accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-multiple="true" data-max-size="52428800">
        <input type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple>
        <div class="dropzone-icon">⬆️</div>
        <p>Drop images here or click to upload</p>
        <small>Supports JPG, PNG, WebP. Maximum 50MB each.</small>
    </div>
    <div class="option-bar">
        <label>Output quality
            <input type="range" min="20" max="95" value="80" data-quality-range>
        </label>
        <strong data-quality-value>80%</strong>
    </div>
    <div class="option-grid">
        <label>Output format
            <select data-format>
                <option value="auto">Keep best match</option>
                <option value="image/jpeg">JPEG</option>
                <option value="image/webp">WebP</option>
                <option value="image/png">PNG</option>
            </select>
        </label>
    </div>
    <div class="action-row action-row left">
        <small class="help" data-estimated-size>Estimated output size: -</small>
    </div>
    <div class="message status" data-status>Ready. Upload your file and start.</div>
    <div class="message error" data-error hidden></div>
    <div class="file-list" data-file-list></div>
    <div class="action-row"><button class="button primary" data-convert disabled>Compress Images</button></div>
    <div class="results" data-results></div>
    <div class="tool-notes">
        <h4>Tip</h4>
        <ul>
            <li>Choose WebP for the strongest compression.</li>
            <li>Original and compressed sizes are shown after processing.</li>
        </ul>
    </div>
</div>

@include('partials.tool-layout-end')
@endsection
