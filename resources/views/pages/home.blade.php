
@extends('layouts.app')

@section('content')

<section class="hero">
    <div class="container hero-inner">
        <span class="eyebrow">Free Online File Conversion — No Signup</span>
        <h1>Convert files.<br>Download instantly.</h1>
        <p>Images, documents, and text tools. Upload a file and get a result in seconds.</p>
        <div class="hero-actions">
            <a href="#tools" class="button primary">Browse All Tools</a>
        </div>
    </div>
</section>

<section id="tools" class="section">
    <div class="container">
        <div class="tool-tabs" role="tablist" aria-label="Tool categories">
            <button class="tool-tab is-active" type="button" role="tab" aria-selected="true"  data-tool-tab="all">All Tools</button>
            <button class="tool-tab" type="button" role="tab" aria-selected="false" data-tool-tab="images">Images</button>
            <button class="tool-tab" type="button" role="tab" aria-selected="false" data-tool-tab="text">Text</button>
        </div>
        <div class="tool-grid home-grid" data-tool-grid>
            @foreach($categories as $key => $category)
                @if(!empty($category['hidden'])) @continue @endif
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

<section class="resume-section">
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
</section>

<section id="how-it-works" class="section muted">
    <div class="container">
        <div class="section-heading center">
            <h2>Simple. Private. Fast.</h2>
            <p>No accounts, no ads. Upload a file, get the result.</p>
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

@endsection
