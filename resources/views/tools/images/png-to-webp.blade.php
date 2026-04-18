@extends('layouts.app')

@section('content')
@include('partials.tool-layout-start')

<div class="tool-app" data-tool="png-to-webp">
    <div class="dropzone" data-dropzone data-hide-on-files="true"
         data-accept=".png,image/png" data-multiple="true" data-max-size="52428800">
        <input type="file" accept=".png,image/png" multiple>
        <div class="dropzone-icon">&#8593;</div>
        <p>Drop PNG images here or click to select</p>
        <small>PNG only &mdash; up to 50 MB each</small>
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
        <label>WebP quality</label>
        <input type="range" min="50" max="100" value="90" data-quality-range>
        <strong data-quality-value>90%</strong>
    </div>

    <div class="action-row left">
        <small class="help" data-estimated-size>Estimated output size: &mdash;</small>
    </div>

    <div class="message status" data-status>Ready. Upload a PNG and hit Convert.</div>
    <div class="message error" data-error hidden></div>
    <div class="file-list" data-file-list></div>
    <div class="action-row">
        <button class="button primary" data-convert disabled>Convert to WebP</button>
    </div>
    <div class="results" data-results></div>

    <div class="tool-notes">
        <h4>Tips</h4>
        <ul>
            <li>WebP typically produces 25&ndash;35% smaller files than PNG.</li>
            <li>Scale below 100% to shrink dimensions and file size together.</li>
            <li>A PNG fallback is included in case you need it.</li>
        </ul>
    </div>
</div>

@include('partials.tool-layout-end')
@endsection
