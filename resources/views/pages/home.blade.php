
@extends('layouts.app')

@section('content')

<section class="hero">
    <div class="container hero-inner">
        <span class="eyebrow">Free Online File Conversion</span>
        <h1>Convert files.<br>No fuss, no signup.</h1>
        <p>Documents, images, and text — processed directly on the server. Clean output every time.</p>
        <div class="hero-actions">
            <a href="#tools" class="button primary">Browse Tools</a>
            <a href="#how-it-works" class="button secondary">How It Works</a>
        </div>
    </div>
</section>
<section id="tools" class="section">
    <div class="container">
        <div class="section-heading center">
            <h2>All Tools</h2>
            <p>Pick a category or browse everything below.</p>
        </div>
        <div class="tool-tabs" role="tablist" aria-label="Tool categories">
            <button class="tool-tab is-active" type="button" role="tab" aria-selected="true"  data-tool-tab="all">All</button>
            <button class="tool-tab" type="button" role="tab" aria-selected="false" data-tool-tab="images">Images</button>
            {{-- <button class="tool-tab" type="button" role="tab" aria-selected="false" data-tool-tab="documents">Documents</button> --}}
            <button class="tool-tab" type="button" role="tab" aria-selected="false" data-tool-tab="text">Text</button>
        </div>
        <div class="tool-grid home-grid" data-tool-grid>
            @foreach($categories as $key => $category)
                @foreach($category['tools'] as $tool)
                    <div data-tool-category="{{ $key }}">
                        @include('partials.tool-card', [
                            'href'        => url('/tools/' . $key . '/' . $tool['slug']),
                            'title'       => $tool['title'],
                            'description' => $tool['description'],
                            'variant'     => $category['variant'] ?? 'default',
                            'slug'        => $tool['slug'],
                        ])
                    </div>
                @endforeach
            @endforeach
        </div>
    </div>
</section>

{{-- <section class="section">
    <div class="container">
        <div class="resume-card" data-last-tool-card hidden>
            <div>
                <strong>Welcome back</strong>
                <p data-last-tool-text>Continue where you left off.</p>
            </div>
            <div class="resume-actions">
                <a href="#" class="button primary small" data-last-tool-link>Open Last Tool</a>
                <button class="button secondary small" type="button" data-last-tool-cancel>Dismiss</button>
            </div>
        </div>
    </div>
</section> --}}

<section id="how-it-works" class="section muted">
    <div class="container">
        <div class="section-heading center">
            <h2>Simple. Reliable. Private.</h2>
            <p>No accounts, no ads, no data harvesting. Just tools that work.</p>
        </div>
        <div class="feature-grid">
            @foreach($features as $feature)
                <article class="feature-card">
                    <span class="feature-emoji">{{ $feature['emoji'] }}</span>
                    <h3>{{ $feature['title'] }}</h3>
                    <p>{{ $feature['description'] }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>



<section class="section muted">
    <div class="container">
        <div class="section-heading center">
            <h2>Why {{ config('tools.brand') }}?</h2>
            <p>Built for real work, not just demos.</p>
        </div>
        <div class="feature-grid">
            <article class="feature-card">
                <span class="feature-emoji">🔒</span>
                <h3>Files Are Not Stored</h3>
                <p>Uploaded files are deleted automatically after processing. Nothing is kept on our servers longer than necessary.</p>
            </article>
            <article class="feature-card">
                <span class="feature-emoji">⚙️</span>
                <h3>Server-Side Processing</h3>
                <p>Conversions run on the server using proven libraries — LibreOffice, Intervention Image, and FPDI.</p>
            </article>
            <article class="feature-card">
                <span class="feature-emoji">📱</span>
                <h3>Works on Any Device</h3>
                <p>No software to install. Open a browser, upload a file, download the result. That's it.</p>
            </article>
        </div>
    </div>
</section>
@endsection
