
@extends('layouts.app')

@section('content')
<section class="hero">
    <div class="container hero-inner">
        <span class="eyebrow">Professional File Conversion</span>
        <h1>Convert files faster,<br><span>ship work sooner</span></h1>
        <p>{{ $config['tagline'] }}</p>
        <div class="hero-actions">
            <a href="#tools" class="button primary">Browse Tools</a>
            <a href="#features" class="button secondary">How It Works</a>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="resume-card" data-last-tool-card hidden>
            <div>
                <strong>Welcome back</strong>
                <p data-last-tool-text>Continue where you left off.</p>
            </div>
            <div class="resume-actions">
                <a href="#" class="button primary small" data-last-tool-link>Open Last Tool</a>
                <button class="button secondary small" type="button" data-last-tool-cancel>Stay Here</button>
            </div>
        </div>
    </div>
</section>

<section id="features" class="section muted">
    <div class="container feature-grid">
        @foreach($features as $feature)
            <article class="feature-card">
                <div class="feature-emoji">{{ $feature['emoji'] }}</div>
                <h3>{{ $feature['title'] }}</h3>
                <p>{{ $feature['description'] }}</p>
            </article>
        @endforeach
    </div>
</section>

<section id="tools" class="section">
    <div class="container">
        <div class="section-heading center">
            <h2>Try the Best Free PDF Online Tools</h2>
            <p>Choose a tab and start converting in seconds.</p>
        </div>
        <div class="tool-tabs" role="tablist" aria-label="Tool categories">
            <button class="tool-tab is-active" type="button" role="tab" aria-selected="true" data-tool-tab="all">All</button>
            <button class="tool-tab" type="button" role="tab" aria-selected="false" data-tool-tab="documents">Convert From PDF</button>
            <button class="tool-tab" type="button" role="tab" aria-selected="false" data-tool-tab="images">Convert To PDF</button>
            <button class="tool-tab" type="button" role="tab" aria-selected="false" data-tool-tab="text">AI Tools</button>
        </div>
        <div class="tool-grid home-grid" data-tool-grid>
            @foreach($categories as $key => $category)
                @foreach($category['tools'] as $tool)
                    <div data-tool-category="{{ $key }}">
                        @include('partials.tool-card', [
                            'href' => url('/tools/' . $key . '/' . $tool['slug']),
                            'title' => $tool['title'],
                            'description' => $tool['description'],
                            'variant' => $category['variant'] ?? 'default',
                            'slug' => $tool['slug'],
                        ])
                    </div>
                @endforeach
            @endforeach
        </div>
    </div>
</section>

<section class="section muted">
    <div class="container">
        <div class="section-heading center">
            <h2>Why Teams Use {{ config('tools.brand') }}</h2>
            <p>Built for people who need accurate conversion and a smooth workflow.</p>
        </div>
        <div class="feature-grid">
            <article class="feature-card">
                <div class="feature-emoji">🧠</div>
                <h3>Smart Conversion Pipeline</h3>
                <p>Hybrid Laravel + Python processing for better reliability and document quality.</p>
            </article>
            <article class="feature-card">
                <div class="feature-emoji">⏱️</div>
                <h3>Fast User Experience</h3>
                <p>Real-time status feedback, smooth upload flow, and cleaner download results.</p>
            </article>
            <article class="feature-card">
                <div class="feature-emoji">🔒</div>
                <h3>Privacy-Focused</h3>
                <p>Temporary processing with controlled file lifecycle and secure backend handling.</p>
            </article>
        </div>
    </div>
</section>
@endsection
