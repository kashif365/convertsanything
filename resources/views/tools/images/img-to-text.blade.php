@extends('layouts.app')

@section('content')
@include('partials.tool-layout-start')

<div class="tool-app" data-tool="img-to-text">
    <div class="dropzone" data-dropzone data-hide-on-files="false"
         data-accept=".jpg,.jpeg,.png,.gif,.bmp,image/jpeg,image/png,image/gif,image/bmp"
         data-multiple="false" data-max-size="5242880">
        <input type="file" accept=".jpg,.jpeg,.png,.gif,.bmp,image/jpeg,image/png,image/gif,image/bmp">
        <div class="dropzone-icon">&#128248;</div>
        <p>Drop an image here or click to select</p>
        <small>JPG, PNG, GIF, BMP &mdash; up to 5 MB &mdash; must contain readable text</small>
    </div>

    <div class="option-grid">
        <label>Language
            <select data-ocr-language>
                <option value="eng" selected>English</option>
                <option value="spa">Spanish</option>
                <option value="fra">French</option>
                <option value="deu">German</option>
                <option value="ita">Italian</option>
                <option value="por">Portuguese</option>
                <option value="chi_sim">Chinese (Simplified)</option>
                <option value="jpn">Japanese</option>
                <option value="kor">Korean</option>
                <option value="ara">Arabic</option>
                <option value="rus">Russian</option>
            </select>
        </label>
    </div>

    <div class="message status" data-status>Ready. Upload an image containing text.</div>
    <div class="message error" data-error hidden></div>
    <div class="file-list" data-file-list></div>

    <div class="action-row">
        <button class="button primary" data-ocr-submit disabled>Extract Text</button>
    </div>

    <div class="ocr-result" data-ocr-result hidden>
        <div class="ocr-meta">
            <span data-ocr-word-count></span>
            <span data-ocr-char-count></span>
        </div>
        <textarea data-ocr-output readonly placeholder="Extracted text will appear here..."></textarea>
        <div class="action-row left" style="margin-top:10px">
            <button class="button secondary small" data-ocr-copy>Copy Text</button>
            <button class="button secondary small" data-ocr-download>Download .txt</button>
            <button class="button secondary small" data-ocr-clear>Clear</button>
        </div>
    </div>

    <div class="tool-notes">
        <h4>Tips</h4>
        <ul>
            <li>Works best on images with clear, horizontal text and good contrast.</li>
            <li>Choose the correct language for better accuracy.</li>
            <li>Scanned documents, receipts, and screenshots work well.</li>
            <li>Maximum file size is 5 MB per image.</li>
        </ul>
    </div>
</div>

@include('partials.tool-layout-end')
@endsection
