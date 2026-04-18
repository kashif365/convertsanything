
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? ((config('tools.brand') ?: 'Covertsanything') . ' - File Conversion Tools') }}</title>
    <meta name="description" content="{{ $metaDescription ?? 'Convert PDF, Word, images, and text files quickly with Covertsanything.' }}">
    <meta name="robots" content="index,follow,max-image-preview:large">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $title ?? ((config('tools.brand') ?: 'Covertsanything') . ' - File Conversion Tools') }}">
    <meta property="og:description" content="{{ $metaDescription ?? 'Convert PDF, Word, images, and text files quickly with Covertsanything.' }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="stylesheet" href="{{ asset('assets/app.css') }}">
</head>
<body>
    @php $visibleCategories = array_filter(config('tools.categories'), fn($c) => empty($c['hidden'])); @endphp
    @include('partials.header', ['categories' => $visibleCategories])
    <main class="site-main">
        @yield('content')
    </main>
    @include('partials.footer', ['categories' => $visibleCategories])
    <script>
        window.appConfig = {
            apiBaseUrl: "{{ url('/api') }}",
            csrfToken: "{{ csrf_token() }}"
        };
    </script>
    <script src="{{ asset('assets/app.js') }}"></script>
</body>
</html>
