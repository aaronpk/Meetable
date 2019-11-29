@extends('layouts/main')

@section('scripts')
@if(env('GOOGLEMAPS_API_KEY'))
<script src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLEMAPS_API_KEY') }}&libraries=places"></script>
<script src="/assets/bulmahead.js"></script>
<script src="/assets/mapsearch.js"></script>
@endif

@endsection

@section('content')

<section class="section">

@if($event->id)
<div class="level">
  <div class="level-left"></div>
  <div class="level-right">

    <div class="dropdown is-right">
        <div class="dropdown-trigger">
            <button class="button" aria-haspopup="true" aria-controls="dropdown-menu">
                <span>Actions</span>
                <span class="icon is-small">@icon(angle-down)</span>
            </button>
        </div>

        <div class="dropdown-menu" id="dropdown-menu" role="menu">
            <div class="dropdown-content">
                <a class="dropdown-item delete-event" href="{{ route('delete-event', $event) }}">
                    <span class="icon">@icon(trash)</span>
                    <span>Delete Event</span>
                </a>
            </div>
        </div>
    </div>

  </div>
</div>
@endif

<div class="content">
    <h1>{{ $event->id ? (($mode == 'clone' ? 'Cloning ' : 'Editing ').$event->name) : 'Add an Event' }}</h1>
</div>

<style>
form h2.subtitle {
    margin-top: 3em;
}
</style>

<form action="{{ $form_action }}" method="post" class="event-form">

    <h2 class="subtitle">What's the name of the event?</h2>

    <div class="field">
        <label class="label">Name</label>
        <input class="input" type="text" autocomplete="off" name="name" value="{{ $event->name }}">
    </div>

    <h2 class="subtitle">Where will the event take place?</h2>

    @if(!$event->id && env('GOOGLEMAPS_API_KEY'))
        <div class="field">
            <label class="label">Location</label>
            <div class="dropdown" style="display: block;">
                <div class="dropdown-trigger" style="">
                    <div class="control has-icons-left">
                        <input class="input" type="text" autocomplete="off" name="location" id="location_search" aria-haspopup="true" aria-controls="location_menu" placeholder="Search for a location">
                        <span class="icon is-left">@icon(search)</span>
                    </div>
                </div>
                <div class="dropdown-menu" id="location_menu" role="menu"></div>
            </div>
        </div>

        <div class="ui message hidden" id="location_preview">
            <div id="map" style="width: 100%; height: 180px; border-radius: 4px; border: 1px #ccc solid;"></div>
        </div>
    @endif

    <div class="field is-grouped">
        <div class="control is-expanded">
            <label class="label">Venue</label>
            <input class="input" type="text" autocomplete="off" name="location_name" value="{{ $event->location_name }}">
        </div>
        <div class="control is-expanded">
            <label class="label">Address</label>
            <input class="input" type="text" autocomplete="off" name="location_address" value="{{ $event->location_address }}">
        </div>
    </div>
    <div class="field is-grouped">
        <div class="control is-expanded">
            <label class="label">City</label>
            <input class="input" type="text" autocomplete="off" name="location_locality" value="{{ $event->location_locality }}">
        </div>
        <div class="control is-expanded">
            <label class="label">State</label>
            <input class="input" type="text" autocomplete="off" name="location_region" value="{{ $event->location_region }}">
        </div>
        <div class="control is-expanded">
            <label class="label">Country</label>
            <input class="input" type="text" autocomplete="off" name="location_country" value="{{ $event->location_country }}">
        </div>
    </div>

    <h2 class="subtitle">When is the event?</h2>

    <div class="field is-grouped">
        <div class="control is-expanded">
            <label class="label">Start Date</label>
            <input class="input" type="date" name="start_date" autocomplete="off" value="{{ $event->start_date }}">
        </div>

        <div class="control is-expanded">
            <label class="label">End Date (optional)</label>
            <input class="input" type="date" name="end_date" autocomplete="off" value="{{ $event->end_date }}">
            <div class="help">for multi-day events</div>
        </div>
    </div>

    <div class="field is-grouped">
        <div class="control is-expanded">
            <label class="label">Start Time (optional)</label>
            <input class="input" type="time" name="start_time" autocomplete="off" value="{{ $event->start_time ?: '' }}">
        </div>

        <div class="control is-expanded">
            <label class="label">End Time (optional)</label>
            <input class="input" type="time" name="end_time" autocomplete="off" value="{{ $event->end_time ?: '' }}">
        </div>
    </div>

    <h2 class="subtitle">Details</h2>

    <div class="field">
        <label class="label">Website</label>
        <input class="input" type="url" autocomplete="off" name="website" value="{{ $event->website }}">
        <div class="help">provide a link to the event's main website if any</div>
    </div>

    <div class="field">
        <label class="label">Description</label>
        <textarea class="input" name="description" style="max-height: none; height: {{ $event->description ? '75vh' : '25vh' }}">{{ $event->description }}</textarea>
        <div class="help">markdown and HTML are supported</div>
    </div>

    <div class="field">
        <label class="label">Tags</label>
        <input class="input" type="text" name="tags" value="{{ $event->tags_string() }}" autocomplete="off">
        <div class="help">space separated, lowercase</div>
    </div>

    <button class="button is-primary" type="submit">Save</button>

    {{ csrf_field() }}
</form>

</section>

@endsection
