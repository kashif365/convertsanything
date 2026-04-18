@extends('layouts.app')

@section('content')
@include('partials.tool-layout-start')

<div class="tool-app" data-tool="image-converter">
    <div class="dropzone" data-dropzone data-hide-on-files="true"
         data-accept="image/jpeg,image/png,image/webp,image/gif,image/bmp,image/tiff"
         data-multiple="true" data-max-size="52428800">
        <input type="file" accept="image/jpeg,image/png,image/webp,image/gif,image/bmp,image/tiff" multiple>
        <div class="dropzone-icon">&#8593;</div>
        <p>Drop any image here or click to select</p>
        <small>JPG, PNG, WebP, GIF, BMP, TIFF &mdash; up to 50 MB each</small>
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
        <label>Output quality</label>
        <input type="range" min="20" max="100" value="85" data-quality-range>
        <strong data-quality-value>85%</strong>
    </div>

    <div class="option-grid">
        <label>Output format
            <select data-convert-format>
                <option value="image/jpeg">JPEG</option>
                <option value="image/png">PNG (lossless)</option>
                <option value="image/webp" selected>WebP (smallest)</option>
            </select>
        </label>
    </div>

    <div class="action-row left">
        <small class="help" data-estimated-size>Estimated output size: &mdash;</small>
    </div>

    <div class="message status" data-status>Ready. Upload an image and hit Convert.</div>
    <div class="message error" data-error hidden></div>
    <div class="file-list" data-file-list></div>
    <div class="action-row">
        <button class="button primary" data-convert disabled>Convert Images</button>
    </div>
    <div class="results" data-results></div>

    <div class="tool-notes">
        <h4>Tips</h4>
        <ul>
            <li>WebP gives the smallest file size for web use.</li>
            <li>Use PNG for graphics that need a transparent background.</li>
            <li>Scale down to reduce dimensions before converting.</li>
        </ul>
    </div>
</div>

@include('partials.tool-layout-end')
@endsection
