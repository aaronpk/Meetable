@extends('layouts/main')

@php
use App\Setting;
@endphp

@section('headtags')
@if(isset($home) && $home)
@if(Setting::value('home_social_image_url') || Setting::value('home_meta_description'))

<meta property="og:type" content="website">
<meta property="og:title" content="{{ env('APP_NAME') }}">
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

    @if(isset($tag))
        <h1 class="title">{{ isset($archive) ? '' : 'Upcoming' }} Events Tagged #{{ $tag }}</h1>
    @elseif(!empty($day))
        <h1 class="title">{{ env('APP_NAME') }} on {{ date('F j, Y', strtotime($year.'-'.$month.'-'.$day)) }}</h1>
    @elseif(!empty($month))
        <h1 class="title">{{ env('APP_NAME') }} in {{ date('F Y', strtotime($year.'-'.$month.'-01')) }}</h1>
    @elseif(!empty($year))
        <h1 class="title">{{ env('APP_NAME') }} in {{ $year }}</h1>
    @else
        <h1 class="title">{{ env('APP_NAME') }}</h1>
    @endif

    @if(isset($tags) && count($tags))
    <div class="tags are-medium">
        @foreach($tags as $t)
          <a href="{{ route('tag', $t->tag) }}" class="tag is-rounded">#{{ $t->tag }}</a>
        @endforeach
    </div>
    @endif

    @if(count($data))
        @include('components/event-list', ['data' => $data])
    @else
        <div class="content"><p>No {{ isset($tag) && !isset($archive) ? 'upcoming ' : '' }}events</p></div>
    @endif

    @if(isset($past_events))
        <h2 class="subtitle" style="margin-top: 2em; font-weight: bold;">Past Events</h2>
        @include('components/event-list', ['data' => $past_events])
    @endif

    @if(isset($tag))
        <div class="">
            <a href="{{ route('tag-archive', $tag) }}">@icon(archive) Tag Archive</a>
        </div>

        <div class="subscribe-ics">
            <a href="{{ route('ics-tag', $tag) }}">@icon(calendar-alt) iCalendar Feed</a>
        </div>
    @elseif(empty($month) && empty($year))
        <div class="subscribe-ics">
            <a href="{{ route('ics-index') }}">@icon(calendar-alt) iCalendar Feed</a>
        </div>
    @endif

</section>
@endsection
