@props(['items' => [], 'crumbs' => [], 'breadcrumbs' => []])

@php
    $rawList = !empty($breadcrumbs) ? $breadcrumbs : (!empty($items) ? $items : $crumbs);
    // Filter out redundant "Home" item if passed in array since Home is always the anchor
    $list = array_values(array_filter($rawList, function ($c) {
        $text = strtolower(trim($c['label'] ?? $c['name'] ?? $c['title'] ?? ''));
        return !empty($text) && $text !== 'home';
    }));
@endphp

@if(!empty($list))
<nav aria-label="Breadcrumb" class="py-2.5 px-4 sm:px-0">
    <ol class="flex items-center space-x-2 text-xs text-slate-600 font-semibold">
        <li class="flex items-center">
            <a href="{{ route('home') }}" class="text-slate-700 hover:text-[#F20B83] font-bold transition-colors inline-flex items-center gap-1">
                <svg class="w-3.5 h-3.5 text-slate-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
                <span>Home</span>
            </a>
        </li>
        @foreach($list as $idx => $crumb)
            @php
                $crumbTitle = $crumb['label'] ?? $crumb['name'] ?? $crumb['title'] ?? '';
            @endphp
            @if(!empty($crumbTitle))
                <li class="inline-flex items-center shrink-0">
                    <svg class="w-3.5 h-3.5 text-slate-400 mx-1.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                    </svg>
                    @if($loop->last || empty($crumb['url']))
                        <span class="font-black text-slate-950" aria-current="page">{{ $crumbTitle }}</span>
                    @else
                        <a href="{{ $crumb['url'] }}" class="text-slate-800 hover:text-[#F20B83] font-black transition-colors">{{ $crumbTitle }}</a>
                    @endif
                </li>
            @endif
        @endforeach
    </ol>
</nav>

<!-- Schema.org BreadcrumbList JSON-LD -->
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        {
            "@@type": "ListItem",
            "position": 1,
            "name": "Home",
            "item": {{ json_encode(route('home')) }}
        }
        @foreach($list as $idx => $crumb)
            @php $crumbTitle = $crumb['label'] ?? $crumb['name'] ?? $crumb['title'] ?? ''; @endphp
            @if(!empty($crumbTitle))
            ,{
                "@@type": "ListItem",
                "position": {{ $idx + 2 }},
                "name": {{ json_encode($crumbTitle) }},
                "item": {{ json_encode($crumb['url'] ?? '') }}
            }
            @endif
        @endforeach
    ]
}
</script>
@endif
