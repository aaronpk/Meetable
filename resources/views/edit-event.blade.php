@extends('layouts/main')

@php
use App\Setting;
@endphp

@section('scripts')
@if(Setting::value('googlemaps_api_key'))
<script src="https://maps.googleapis.com/maps/api/js?key={{ Setting::value('googlemaps_api_key') }}&libraries=places"></script>
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

    @if($event->id)
        <p><a href="{{ $event->permalink() }}">@icon(arrow-circle-left) {{ $event->name }}</a></p>
    @endif
</div>

@if($mode == 'create')
    @if($message = \App\Setting::html_value('add_an_event'))
        <article class="message is-primary">
            <div class="message-body content">{!! $message !!}</div>
        </article>
    @endif
@endif

<style>
form h2.subtitle {
    margin-top: 3em;
}
</style>

<form action="{{ $form_action }}" method="post" class="event-form">

    <h2 class="subtitle">What's the name of the event?</h2>

    <div class="field">
        <input class="input" type="text" autocomplete="off" name="name" value="{{ $event->name }}">
    </div>

    <!-- cover photo will be cropped to 1440x640 -->
    <h2 class="subtitle">Add a cover image (optional)</h2>

    <div id="cover-photo-preview" class="{{ $event->cover_image ? '' : 'hidden' }} has-delete">
        <button class="delete"></button>
        <img src="{{ $event->cover_image }}" width="720" height="320">
    </div>

    <div class="field" id="upload-cover-field">
        <div class="file is-boxed">
            <label class="file-label" style="width: 100%;">
                <input id="cover-image-input-field" class="file-input" type="file" accept=".jpg,.png,image/jpeg,image/png">
                <span class="file-cta" id="drop-area">
                    <span class="file-icon">@icon(upload)</span>
                    <span class="file-icon-loading hidden">@spinning_icon(spinner)</span>
                    <span class="file-label">Choose an image...</span>
                </span>
                <span class="file-name hidden"></span>
            </label>
        </div>
    </div>
    <div class="help">cover images should be at least 1440px wide and will be cropped to 1440x640</div>


    <h2 class="subtitle">Where will the event take place?</h2>

    @if(Setting::value('googlemaps_api_key'))
        <div class="field">
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
            <div id="map" data-latitude="{{ $event->latitude }}" data-longitude="{{ $event->longitude }}" style="width: 100%; height: 180px; border-radius: 4px; border: 1px #ccc solid;"></div>
        </div>
    @endif

    <div class="field is-grouped is-grouped-multiline">
        <div class="control is-expanded">
            <label class="label">Venue</label>
            <input class="input" type="text" autocomplete="off" name="location_name" value="{{ $event->location_name }}">
        </div>
        <div class="control is-expanded">
            <label class="label">Address</label>
            <input class="input" type="text" autocomplete="off" name="location_address" value="{{ $event->location_address }}">
        </div>
    </div>
    <div class="field is-grouped is-grouped-multiline">
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

    <div class="field is-grouped is-grouped-multiline">
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

    <div class="field is-grouped is-grouped-multiline" id="time-fields">
        <div class="control is-expanded">
            <label class="label">Start Time (optional)</label>
            <input class="input" type="time" name="start_time" autocomplete="off" value="{{ $event->start_time ?: '' }}">
            <div class="help">leave start time blank for multi-day events</div>
        </div>

        <div class="control is-expanded">
            <label class="label">End Time (optional)</label>
            <input class="input" type="time" name="end_time" autocomplete="off" value="{{ $event->end_time ?: '' }}">
            <div class="help">leave end time blank for multi-day events</div>
        </div>
    </div>

    <div class="field hidden" id="timezone-field">
        <div class="control is-expanded">
            <label class="label">Timezone (optional)</label>
            <div class="select is-fullwidth">
                <select name="timezone">
                    @foreach(\App\Event::timezones() as $tz)
                        <option value="{{ $tz }}" {{ $event->timezone == $tz ? 'selected' : '' }} {{ $tz == '──────────' ? 'disabled' : '' }}>{{ $tz }}</option>
                    @endforeach
                </select>
            </div>
            <div class="help">only used when there is a start/end time</div>
        </div>
    </div>

    <h2 class="subtitle">Details</h2>

    <div class="field">
        <label class="label">Website</label>
        <input class="input" type="url" autocomplete="off" name="website" value="{{ $event->website }}">
        <div class="help">provide a link to the event's main website if any</div>
    </div>

    @if(Setting::value('enable_ticket_url'))
    <div class="field">
        <label class="label">Registration URL</label>
        <input class="input" type="url" autocomplete="off" name="tickets_url" value="{{ $event->tickets_url }}">
        <div class="help">if the event requires registration, link to the registration page here. this will also disable RSVPs on this website.</div>
    </div>
    @endif

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

    <input type="hidden" name="latitude" value="{{ $event->latitude }}">
    <input type="hidden" name="longitude" value="{{ $event->longitude }}">
    <input type="hidden" name="cover_image" id="cover-photo-filename" value="{{ $event->cover_image }}">

    {{ csrf_field() }}
</form>

<script>
function handleFiles(files) {

    var formData = new FormData();
    formData.append("image", files[0]);
    formData.append("_token", csrf_token());

    var request = new XMLHttpRequest();
    request.open("POST", "{{ route('upload-event-cover-image') }}");
    request.onreadystatechange = function() {
        if(request.readyState == XMLHttpRequest.DONE) {
            handleFileUploadResponse(request.responseText);
        }
    }
    request.send(formData);

    $("#upload-cover-field .file-icon").addClass("hidden");
    $("#upload-cover-field .file-icon-loading").removeClass("hidden");
}

function handleFileUploadResponse(response) {
    var data = JSON.parse(response);

    $("#cover-photo-filename").val(data.url);
    $("#cover-photo-preview img").attr("src", data.url);
    $("#cover-photo-preview").removeClass("hidden");

    $("#upload-cover-field .file-icon").removeClass("hidden");
    $("#upload-cover-field .file-icon-loading").addClass("hidden");
}

$(function(){

    $("#cover-image-input-field").on("change", function(evt){
        handleFiles(evt.target.files);
    });

    $("#cover-photo-preview .delete").click(function(evt){
        evt.preventDefault();
        $("#cover-photo-filename").val("");
        $("#cover-photo-preview img").attr("src", "");
        $("#cover-photo-preview").addClass("hidden");
    });

    $("input[name=start_time]").on('change', function(){
        if($(this).val()) {
            $("#timezone-field").removeClass('hidden');
        } else {
            $("#timezone-field").addClass('hidden');
        }
    });

    $("input[name=end_date]").on('change', function(){
        if($(this).val()) {
            $("#time-fields").addClass('hidden');
        } else {
            $("#time-fields").removeClass('hidden');
        }
    });

});
</script>

</section>

@endsection
