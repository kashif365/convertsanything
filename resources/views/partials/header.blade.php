
<header class="site-header">
    <div class="container header-inner">
        <a href="{{ route('home') }}" class="brand">
            <span class="brand-mark">CA</span>
            <span class="brand-name">{{ config('tools.brand') }}</span>
        </a>
        <nav class="desktop-nav">
            @foreach($categories as $key => $category)
                <div class="nav-group">
                    <a href="{{ route('tools.category', $key) }}">{{ $category['title'] }}</a>
                    <div class="nav-dropdown">
                        @foreach($category['tools'] as $item)
                            <a href="{{ url('/tools/' . $key . '/' . $item['slug']) }}">{{ $item['title'] }}</a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>
        <a href="{{ route('home') }}#tools" class="button primary small">Get Started</a>
    </div>
</header>
