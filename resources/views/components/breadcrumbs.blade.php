@props(['crumbs' => []])

<nav class="flex text-xs text-slate-400 space-x-2 items-center" aria-label="Breadcrumb">
    <a href="{{ route('home') }}" class="hover:text-white transition">Home</a>
    @foreach($crumbs as $crumb)
        <span class="text-slate-600 select-none">/</span>
        @if($loop->last || empty($crumb['url']))
            <span class="text-white font-medium" aria-current="page">{{ $crumb['name'] }}</span>
        @else
            <a href="{{ $crumb['url'] }}" class="hover:text-white transition">{{ $crumb['name'] }}</a>
        @endif
    @endforeach
</nav>

