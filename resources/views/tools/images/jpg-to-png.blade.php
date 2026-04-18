@extends('layouts.app')

@section('content')
@include('partials.tool-layout-start')

<div class="tool-app" data-tool="jpg-to-png">
    <div class="dropzone" data-dropzone data-hide-on-files="true"
         data-accept=".jpg,.jpeg,image/jpeg" data-multiple="true" data-max-size="52428800">
        <input type="file" accept=".jpg,.jpeg,image/jpeg" multiple>
        <div class="dropzone-icon">&#8593;</div>
        <p>Drop JPG images here or click to select</p>
        <small>JPG / JPEG only &mdash; up to 50 MB each</small>
    </div>

    <div class="option-bar">
        <label>
            Output Scale
            <small data-scale-dims>Upload an image to see output dimensions</small>
        </label>
        <input type="range" min="10" max="200" value="100" data-scale-range>
        <strong data-scale-value>100%</strong>
    </div>

    <div class="action-row left">
        <small class="help" data-estimated-size>Estimated output size: &mdash;</small>
    </div>

    <div class="message status" data-status>Ready. Upload a JPG and hit Convert.</div>
    <div class="message error" data-error hidden></div>
    <div class="file-list" data-file-list></div>
    <div class="action-row">
        <button class="button primary" data-convert disabled>Convert to PNG</button>
    </div>
    <div class="results" data-results></div>

    <div class="tool-notes">
        <h4>Tips</h4>
        <ul>
            <li>PNG is lossless &mdash; good for logos, screenshots, and graphics.</li>
            <li>Scale below 100% to reduce image size before converting.</li>
        </ul>
    </div>
</div>

@include('partials.tool-layout-end')
@endsection
