@extends('layouts.app')

@section('content')
@include('partials.tool-layout-start')

<div class="tool-app" data-tool="resizer">
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

    <div class="option-grid three">
        <label>Width (px)
            <input type="number" value="1200" min="1" max="10000" data-width>
        </label>
        <label>Height (px)
            <input type="number" value="800" min="1" max="10000" data-height>
        </label>
        <label class="checkbox-label">
            <input type="checkbox" checked data-lock-aspect>
            Lock aspect ratio
        </label>
    </div>

    <div class="action-row left">
        <small class="help" data-estimated-size>Estimated output size: &mdash;</small>
    </div>

    <div class="message status" data-status>Ready. Upload an image and hit Resize.</div>
    <div class="message error" data-error hidden></div>
    <div class="file-list" data-file-list></div>
    <div class="action-row">
        <button class="button primary" data-convert disabled>Resize Images</button>
    </div>
    <div class="results" data-results></div>

    <div class="tool-notes">
        <h4>Tips</h4>
        <ul>
            <li>The scale slider resizes relative to the original dimensions.</li>
            <li>Or enter exact pixel values in the width/height fields.</li>
            <li>Lock aspect ratio keeps the image proportions intact.</li>
        </ul>
    </div>
</div>

@include('partials.tool-layout-end')
@endsection
