
@php
    $iconMap = [
        'pdf-to-word'    => ['label' => 'DOC',  'tone' => 'blue'],
        'word-to-pdf'    => ['label' => 'PDF',  'tone' => 'red'],
        'pdf-merge'      => ['label' => 'PDF',  'tone' => 'amber'],
        'pdf-split'      => ['label' => 'PDF',  'tone' => 'purple'],
        'jpg-to-png'     => ['label' => 'PNG',  'tone' => 'pink'],
        'png-to-webp'    => ['label' => 'WEB',  'tone' => 'teal'],
        'image-converter'=> ['label' => 'IMG',  'tone' => 'indigo'],
        'compressor'     => ['label' => 'ZIP',  'tone' => 'orange'],
        'resizer'        => ['label' => 'SIZE', 'tone' => 'cyan'],
        'img-to-text'    => ['label' => 'TXT',  'tone' => 'green'],
        'word-counter'   => ['label' => 'WRD',  'tone' => 'green'],
        'case-converter' => ['label' => 'CASE', 'tone' => 'slate'],
    ];
    $toolIcon = $iconMap[$slug ?? ''] ?? ['label' => 'TOOL', 'tone' => 'slate'];
@endphp

<a href="{{ $href }}" class="tool-card {{ $variant ?? 'default' }}">
    <div class="tool-icon tone-{{ $toolIcon['tone'] }}" aria-hidden="true">
        <span class="file-badge">
            <span class="file-badge-label">{{ $toolIcon['label'] }}</span>
        </span>
    </div>
    <h3>{{ $title }}</h3>
    <p>{{ $description }}</p>
    <span class="tool-link">Use Tool &rarr;</span>
</a>
