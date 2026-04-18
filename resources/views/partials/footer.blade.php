
<footer class="site-footer">
    <div class="container footer-grid">
        <div class="footer-brand">
            <a href="{{ route('home') }}" class="brand">
                <span class="brand-mark">CA</span>
                <span class="brand-name">{{ config('tools.brand') }}</span>
            </a>
            <p>Free file conversion tools for documents, images, and text. No signup required.</p>
        </div>
        @foreach($categories as $key => $category)
            <div>
                <h3>{{ $category['title'] }}</h3>
                <ul>
                    @foreach($category['tools'] as $tool)
                        <li><a href="{{ url('/tools/' . $key . '/' . $tool['slug']) }}">{{ $tool['title'] }}</a></li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
    <div class="container footer-bottom">
        <p>&copy; {{ date('Y') }} {{ config('tools.brand') }}. All rights reserved.</p>
        <div class="footer-links">
            <a href="#">Privacy</a>
            <a href="#">Terms</a>
        </div>
    </div>
</footer>
