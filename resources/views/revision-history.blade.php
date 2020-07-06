@extends('layouts/main')

@section('content')
<section class="section narrow">

<div class="content">
    <h1>Revision History for "{{ $event->name }}"</h1>

    <p><a href="{{ $event->permalink() }}">@icon(arrow-circle-left) {{ $event->name }}</a></p>
</div>

@php($currentRevision = null)

<ul class="changelog">
@foreach($revisions as $previousRevision)
	@if(!$currentRevision)
		@php($currentRevision = $previousRevision)
	@else
		<li>
			<div class="left">
				<div class="change">
					<a href="{{ route('view-revision-diff', [$event, $currentRevision]) }}">
						{{ $currentRevision->edit_summary ?: '(no comment)' }}
					</a>
				</div>

				<div class="change">
					<span style="font-family: monospace; font-size: 13px">{{ implode(', ', $currentRevision->changed_fields($previousRevision)) }}</span>
				</div>

				<div class="meta">
					<a href="{{ $currentRevision->lastModifiedBy->url }}" class="author">
						{{ $currentRevision->lastModifiedBy->display_name() }}
					</a>

					made

					{{ $currentRevision->num_changed_fields($previousRevision) }} changes

					on 

					<span title="{{ $currentRevision->updated_at->format('c') }}">
						{{ $currentRevision->updated_at->format('F j, Y') }}
					</span>
				</div>
			</div>
			<div class="right">
				<a href="{{ route('view-revision-diff', [$event, $currentRevision]) }}" class="ui button is-small is-light">
					diff
				</a>
				<a href="{{ route('view-revision', [$event, $currentRevision]) }}" class="ui button is-small is-light">
					<span class="icon">@icon(eye)</span>
				</a>
			</div>
		</li>
		@php($currentRevision = $previousRevision)
	@endif
@endforeach
	
@if($currentRevision)
		<li>
			<div class="left">
				<div class="meta">
					<a href="{{ $currentRevision->lastModifiedBy->url }}" class="author">
						{{ $currentRevision->lastModifiedBy->display_name() }}
					</a>
					<a href="{{ route('view-revision', [$event, $currentRevision]) }}">
						created this event on
						<span title="{{ $currentRevision->updated_at->format('c') }}">
							{{ $currentRevision->created_at->format('F j, Y') }}
						</span>
					</a>
				</div>

			</div>
			<div class="right">
				<a href="{{ route('view-revision', [$event, $currentRevision]) }}" class="ui button is-small is-light">
					<span class="icon">@icon(eye)</span>
				</a>
			</div>
		</li>
@endif
</ul>

<style>
ul.changelog {
	list-style-type: none;
	margin: 0;
	padding: 0;
}
ul.changelog li {
	padding: 8px;
	border: 1px solid #e1e4e8;
	border-bottom: none;
	display: flex;
	align-items: center;
}
ul.changelog li:first-of-type {
	border-top-left-radius: 4px;
	border-top-right-radius: 4px;
}
ul.changelog li:last-of-type {
	border-bottom-left-radius: 4px;
	border-bottom-right-radius: 4px;
	border-bottom: 1px solid #e1e4e8;
}
ul.changelog .meta {
	font-size: 0.8em;
}
ul.changelog .meta a {
	color: #4a4a4a;
}
ul.changelog .meta a.author {
	font-weight: bold;
}
ul.changelog .left {
	flex: auto;
}
ul.changelog .right {
	text-align: right;
}
ul.changelog .right a {
	margin-bottom: 1px;
	margin-top: 1px;
}
</style>

</section>
@endsection
