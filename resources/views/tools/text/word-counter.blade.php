@extends('layouts.app')

@section('content')
@include('partials.tool-layout-start')

<div class="tool-app text-tool" data-tool="word-counter">
    <textarea data-text-input placeholder="Paste or type your text here..."></textarea>
    <div class="action-row left">
        <button class="button secondary small" data-copy>Copy</button>
        <button class="button secondary small" data-clear>Clear</button>
    </div>
    <div class="stats-grid" data-stats></div>
    <div class="info-card">
        <strong>How it works</strong>
        <ul>
            <li>Reading time uses about 225 words per minute.</li>
            <li>Speaking time uses about 150 words per minute.</li>
            <li>Paragraphs are counted using blank-line separation.</li>
        </ul>
    </div>
</div>

@include('partials.tool-layout-end')
@endsection
