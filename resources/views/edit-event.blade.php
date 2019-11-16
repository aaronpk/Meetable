@extends('layouts/main')

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

<form action="{{ $form_action }}" method="post">

    <div class="field">
        <label class="label">Name</label>
        <input class="input" type="text" autocomplete="off" name="name" value="{{ $event->name }}">
    </div>

    @if($event->id)
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
    @else
        <div class="field">
            <label class="label">Location</label>
            <input class="input" type="text" autocomplete="off" name="location" id="location_search">
        </div>

        <div class="ui message hidden" id="location_preview">
        </div>
    @endif

    <div class="field is-grouped">
        <div class="control is-expanded">
            <label class="label">Start Date</label>
            <input class="input" type="date" name="start_date" autocomplete="off" value="{{ $event->start_date }}">
        </div>

        <div class="control is-expanded">
            <label class="label">End Date</label>
            <input class="input" type="date" name="end_date" autocomplete="off" value="{{ $event->end_date }}">
            <div class="help">optional, for multi-day events</div>
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

    <div class="field">
        <label class="label">Website</label>
        <input class="input" type="url" autocomplete="off" name="website" value="{{ $event->website }}">
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

    <button class="button is-primary" type="submit">Submit</button>

    {{ csrf_field() }}
</form>

</section>

@endsection
