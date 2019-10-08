@extends('layouts/main')

@section('content')

<div class="ui container">

<h1>{{ $event->id ? 'Editing '.$event->name : 'Add an Event' }}</h1>

<form action="{{ $event->id ? route('save-event', $event) : route('create-event') }}" method="post" class="ui form">

    <div class="field">
        <label>Name</label>
        <input type="text" autocomplete="off" name="name" value="{{ $event->name }}">
    </div>

    @if($event->id)
        <div class="two fields">
            <div class="field">
                <label>Venue</label>
                <input type="text" autocomplete="off" name="location_name" value="{{ $event->location_name }}">
            </div>
            <div class="field">
                <label>Address</label>
                <input type="text" autocomplete="off" name="location_address" value="{{ $event->location_address }}">
            </div>
        </div>
        <div class="three fields">
            <div class="field">
                <label>City</label>
                <input type="text" autocomplete="off" name="location_locality" value="{{ $event->location_locality }}">
            </div>
            <div class="field">
                <label>State</label>
                <input type="text" autocomplete="off" name="location_region" value="{{ $event->location_region }}">
            </div>
            <div class="field">
                <label>Country</label>
                <input type="text" autocomplete="off" name="location_country" value="{{ $event->location_country }}">
            </div>
        </div>
    @else
        <div class="field">
            <label>Location</label>
            <input type="text" autocomplete="off" name="location" id="location_search">
        </div>

        <div class="ui message hidden" id="location_preview">
        </div>
    @endif

    <div class="two fields">
        <div class="field">
            <label>Start Date</label>
            <input type="date" name="start_date" autocomplete="off" value="{{ $event->start_date }}">
        </div>

        <div class="field">
            <label>End Date (optional, for multi-day events)</label>
            <input type="date" name="end_date" autocomplete="off" value="{{ $event->end_date }}">
        </div>
    </div>

    <div class="two fields">
        <div class="field">
            <label>Start Time (optional)</label>
            <input type="time" name="start_time" autocomplete="off" value="{{ $event->start_time }}">
        </div>

        <div class="field">
            <label>End Time (optional)</label>
            <input type="time" name="end_time" autocomplete="off" value="{{ $event->end_time }}">
        </div>
    </div>

    <div class="field">
        <label>Website</label>
        <input type="url" autocomplete="off" name="website" value="{{ $event->website }}">
    </div>

    <div class="field">
        <label>Description (markdown and HTML are supported)</label>
        <textarea name="description" style="max-height: none; height: {{ $event->description ? '75vh' : '25vh' }}">{{ $event->description }}</textarea>
    </div>

    <div class="field">
        <label>Tags (space-separated)</label>
        <input type="text" name="tags" value="{{ $event->tags_string() }}">
    </div>

    <button class="ui button" type="submit">Submit</button>

    {{ csrf_field() }}
</form>

</div>

@endsection
