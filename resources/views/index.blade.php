@extends('layouts/main')

@php
use App\Setting;
@endphp

@section('headtags')
@if(isset($home) && $home)
@if(Setting::value('home_social_image_url') || Setting::value('home_meta_description'))

<meta property="og:type" content="website">
<meta property="og:title" content="{{ $page_title }}">
@if(Setting::value('home_meta_description'))
<meta property="og:description" content="{{ Setting::value('home_meta_description') }}">
<meta property="description" content="{{ Setting::value('home_meta_description') }}">
@endif
<meta property="og:url" content="{{ route('index') }}">
@if(Setting::value('home_social_image_url'))
<meta property="og:image" content="{{ Setting::value('home_social_image_url') }}">
@endif
<meta name="twitter:card" content="summary_large_image">

@endif
@endif
@endsection

@section('content')

<style>
.event-list .subtitle.month {
    font-size: 1.5em;
}
.event-list .event {
    margin-left: 2em;
}
</style>
<section class="section">

    <h1 class="title">{{ $page_title }}</h1>

    @if(isset($tags) && count($tags))
    <div class="tags {{ isset($page_type) && $page_type == 'tag' ? 'are-large' : 'are-medium' }}">
        @foreach($tags as $t)
          <a href="{{ route('tag', $t->tag) }}" class="tag is-hoverable is-rounded {{ isset($page_type) && $page_type == 'tag' ? 'is-dark' : '' }}">#{{ $t->tag }}</a>
        @endforeach
    </div>
    @endif

    @if(count($data))
        @include('components/event-list', ['data' => $data])
    @else
        <div class="content"><p>No {{ isset($tag) && !isset($archive) ? 'upcoming ' : '' }}events</p><div class="h-feed"></div></div>
    @endif

    @if(isset($past_events) && count($past_events))
        <h2 class="subtitle" style="margin-top: 2em; font-weight: bold;">Past Events</h2>
        @include('components/event-list', ['data' => $past_events])
    @endif

    @if(isset($page_type) && $page_type == 'tag')
        <div class="">
            <a href="{{ route('tag-archive', implode(',',array_map(function($t){ return $t->tag; }, $tags))) }}">@icon(archive) Tag Archive</a>
        </div>

        <div class="subscribe-ics">
            <a href="{{ route('ics-tag-preview', implode(',',array_map(function($t){ return $t->tag; }, $tags))) }}">@icon(calendar-alt) iCalendar Feed</a>
        </div>
    @elseif(empty($month) && empty($year))
        <div class="subscribe-ics">
            <a href="{{ route('ics-index-preview') }}">@icon(calendar-alt) iCalendar Feed</a>
        </div>
    @endif

</section>
@endsection
