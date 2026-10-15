{{-- Tells feed readers and browsers where the neighbouring pages of a paged list are --}}
@if(isset($page))
@if(!empty($page['newer']))
<link rel="prev" href="{{ $page['newer'] }}">
@endif
@if(!empty($page['older']))
<link rel="next" href="{{ $page['older'] }}">
@endif
@endif
