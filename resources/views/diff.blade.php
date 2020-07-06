@extends('layouts/main')

@section('content')
<section class="section">

<style>
.nav-links {
	display: flex;
}
.nav-links a {
	flex: 1;
}
.nav-links a:last-of-type {
	text-align: right;
}
.diffs {
	margin-top: 1em;
}
td.field-name {
	width: 120px;
	font-family: monospace;
	font-size: 13px;
	padding: .5em !important;
	vertical-align: middle !important;
}
td.diff-container {
	padding: 0 !important;
	vertical-align: middle !important;
}
td.diff-container td {
	border: none !important;
}
{!! \Jfcherng\Diff\DiffHelper::getStyleSheet() !!}
.diff-wrapper thead {
	display: none;
}
.diff-wrapper.diff {
	border: none;
}
.tag-diff {
	font-family: monospace;
	color: black;
	font-size: 13px;
	background: #fef6d9;
	padding: 0 !important;
}
.tag-diff .added {
	background-color: #94f094;
	font-weight: bold;
}
.tag-diff .removed {
	background-color: #f09494;
	font-weight: bold;
}
</style>

<div class="content">
	<h1>Differences between revisions of "{{ $current->name }}"</h1>

	<div class="meta">
		<a href="{{ $current->lastModifiedBy->url }}" class="author">
			{{ $current->lastModifiedBy->display_name() }}
		</a>

		made

		{{ $current->num_changed_fields($previous) }} changes

		on 

		<span title="{{ $current->updated_at->format('c') }}">
			{{ $current->updated_at->format('F j, Y') }}
			at 
			{{ $current->updated_at->format('g:ia') }} UTC
		</span>
	</div>

	<div class="narrow">

		<div style="margin-top: 1em;" class="nav-links">
			<a href="{{ route('revision-history', $event_id) }}">@icon(arrow-circle-left) back</a>
			<a href="{{ route('view-revision', [$event_id, $current]) }}">@icon(eye) view</a>
		</div>

		<table class="diffs">
		@foreach($current->changed_fields($previous) as $field)
			<tr>
				<td class="field-name">{{ $field }}</td>
				@switch($field)
					@case('tags')
						@php
							$previousTags = json_decode($previous->tags, true) ?: [];
							$currentTags = json_decode($current->tags, true) ?: [];
						@endphp
						<td class="tag-diff">
							<table class="diff-wrapper diff diff-html diff-combined">
								<tbody class="change change-rep">
									<tr data-type="-">
										<td class="old">@if($previousTags) @foreach($previousTags as $tag)<span class="{{ !in_array($tag, $currentTags) ? 'removed' : '' }}">{{ $tag }}</span> @endforeach @endif</td>
									</tr>
									<tr data-type="+">
										<td class="new">@if($currentTags) @foreach($currentTags as $tag)<span class="{{ !in_array($tag, $previousTags) ? 'added' : '' }}">{{ $tag }}</span> @endforeach @endif</td>
									</tr>
								</tbody>
							</table>
						</td>
						@break
					@default
						<td class="diff-container">
							{!! Jfcherng\Diff\DiffHelper::calculate($previous->{$field} ?: '', $current->{$field} ?: '', 'Combined', [
									'detailLevel' => 'word',
									'lineNumbers' => false,
								]) 
							!!}
						</td>
				@endswitch
			</tr>
		@endforeach
		</table>

	</div>

</div>
</section>
@endsection


