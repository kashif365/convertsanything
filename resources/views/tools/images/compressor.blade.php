@extends('layouts.app')

@section('content')
@include('partials.tool-layout-start')

<div class="tool-app" data-tool="compressor">
    <div class="dropzone" data-dropzone data-hide-on-files="true"
         data-accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
         data-multiple="true" data-max-size="52428800">
        <input type="file" accept=".jpg,.jpeg,.png,.webp" multiple>
        <div class="dropzone-icon">&#8593;</div>
        <p>Drop images here or click to select</p>
        <small>JPG, PNG, WebP &mdash; up to 50 MB each</small>
    </div>

    <div class="option-bar">
        <label>
            Output Scale
            <small data-scale-dims>Upload an image to see output dimensions</small>
        </label>
        <input type="range" min="10" max="200" value="100" data-scale-range>
        <strong data-scale-value>100%</strong>
    </div>

    <div class="option-bar">
        <label>Compression quality</label>
        <input type="range" min="20" max="95" value="80" data-quality-range>
        <strong data-quality-value>80%</strong>
    </div>

    <div class="option-grid">
        <label>Output format
            <select data-format>
                <option value="auto">Keep source format</option>
                <option value="image/jpeg">JPEG</option>
                <option value="image/webp">WebP (smallest)</option>
                <option value="image/png">PNG (lossless)</option>
            </select>
        </label>
    </div>

    <div class="action-row left">
        <small class="help" data-estimated-size>Estimated output size: &mdash;</small>
    </div>

    <div class="message status" data-status>Ready. Upload an image and hit Compress.</div>
    <div class="message error" data-error hidden></div>
    <div class="file-list" data-file-list></div>
    <div class="action-row">
        <button class="button primary" data-convert disabled>Compress Images</button>
    </div>
    <div class="results" data-results></div>

    <div class="tool-notes">
        <h4>Tips</h4>
        <ul>
            <li>WebP gives the best compression for web images.</li>
            <li>Scale below 100% to reduce both dimensions and file size at once.</li>
            <li>Original vs. compressed sizes are shown after processing.</li>
        </ul>
    </div>
</div>

@include('partials.tool-layout-end')
@endsection
