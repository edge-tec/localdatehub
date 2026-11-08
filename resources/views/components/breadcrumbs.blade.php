@props(['items' => [], 'crumbs' => [], 'breadcrumbs' => []])

@php
    $list = !empty($breadcrumbs) ? $breadcrumbs : (!empty($items) ? $items : $crumbs);
@endphp

@if(!empty($list))
<nav aria-label="Breadcrumb" class="py-3 px-4 sm:px-0">
    <ol class="flex items-center space-x-2 text-xs text-slate-400">
        <li class="flex items-center">
            <a href="{{ route('home') }}" class="hover:text-rose-400 transition-colors">Home</a>
        </li>
        @foreach($list as $idx => $crumb)
            <li class="flex items-center truncate">
                <svg class="w-3.5 h-3.5 text-slate-600 mx-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
                @if($loop->last || empty($crumb['url']))
                    <span class="font-medium text-slate-200 truncate" aria-current="page">{{ $crumb['name'] ?? $crumb['title'] ?? '' }}</span>
                @else
                    <a href="{{ $crumb['url'] }}" class="hover:text-rose-400 transition-colors">{{ $crumb['name'] ?? $crumb['title'] ?? '' }}</a>
                @endif
            </li>
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
        ,{
            "@@type": "ListItem",
            "position": {{ $idx + 2 }},
            "name": {{ json_encode($crumb['name'] ?? $crumb['title'] ?? '') }},
            "item": {{ json_encode($crumb['url'] ?? '') }}
        }
        @endforeach
    ]
}
</script>
@endif
