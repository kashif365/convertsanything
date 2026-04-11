@extends('layouts.app')

@section('content')
@include('partials.tool-layout-start')

<div class="tool-app" data-tool="jpg-to-png">
    <div class="dropzone" data-dropzone data-hide-on-files="true" data-accept=".jpg,.jpeg,image/jpeg" data-multiple="true" data-max-size="52428800">
        <input type="file" accept=".jpg,.jpeg,image/jpeg" multiple>
        <div class="dropzone-icon">⬆️</div>
        <p>Drop JPG/JPEG images here or click to upload</p>
        <small>Maximum file size: 50MB</small>
    </div>
    <div class="option-bar">
        <label>PNG conversion quality
            <input type="range" min="50" max="100" value="90" data-quality-range>
        </label>
        <strong data-quality-value>90%</strong>
    </div>
    <div class="message status" data-status>Ready. Upload your file and start.</div>
    <div class="action-row action-row left">
        <small class="help" data-estimated-size>Estimated output size: -</small>
    </div>
    <div class="message error" data-error hidden></div>
    <div class="file-list" data-file-list></div>
    <div class="action-row"><button class="button primary" data-convert disabled>Convert to PNG</button></div>
    <div class="results" data-results></div>
    <div class="tool-notes">
        <h4>Tip</h4>
        <ul>
            <li>Use PNG for crisp graphics or logos.</li>
            <li>Converted file size is shown after processing.</li>
        </ul>
    </div>
</div>

@include('partials.tool-layout-end')
@endsection
