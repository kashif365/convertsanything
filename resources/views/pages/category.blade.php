
@extends('layouts.app')

@section('content')
<div class="container category-shell">
    <a href="{{ route('home') }}" class="back-link">&larr; Back to Home</a>
    <div class="section-heading left">
        <div class="group-icon large">{{ strtoupper(substr($category['title'], 0, 1)) }}</div>
        <div>
            <h1>{{ $category['title'] }}</h1>
            <p>{{ $category['description'] }}</p>
        </div>
    </div>
    <div class="tool-grid">
        @foreach($category['tools'] as $tool)
            @include('partials.tool-card', [
                'href'        => url('/tools/' . $categoryKey . '/' . $tool['slug']),
                'title'       => $tool['title'],
                'description' => $tool['description'],
                'slug'        => $tool['slug'],
                'variant'     => $category['variant'] ?? 'default',
            ])
        @endforeach
    </div>
</div>
@endsection
