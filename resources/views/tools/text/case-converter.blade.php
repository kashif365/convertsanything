@extends('layouts.app')

@section('content')
@include('partials.tool-layout-start')

<div class="tool-app text-tool" data-tool="case-converter">
    <textarea data-text-input placeholder="Paste or type your text here..."></textarea>
    <div class="case-grid">
        <button type="button" data-case="upper">UPPERCASE</button>
        <button type="button" data-case="lower">lowercase</button>
        <button type="button" data-case="sentence">Sentence case</button>
        <button type="button" data-case="title">Title Case</button>
        <button type="button" data-case="capitalize">Capitalized</button>
        <button type="button" data-case="alternating">aLtErNaTiNg</button>
        <button type="button" data-case="inverse">InVeRsE</button>
    </div>
    <div class="action-row left">
        <button class="button primary small" data-copy>Copy Result</button>
        <button class="button secondary small" data-clear>Clear</button>
    </div>
    <div class="info-card">
        <strong>Tips</strong>
        <ul>
            <li>Sentence case capitalizes after periods, exclamation marks, and question marks.</li>
            <li>Title Case is useful for headlines and titles.</li>
            <li>Inverse swaps existing uppercase and lowercase letters.</li>
        </ul>
    </div>
</div>

@include('partials.tool-layout-end')
@endsection
