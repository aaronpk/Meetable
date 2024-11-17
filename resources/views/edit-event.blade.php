@extends('layouts/main')

@php
use App\Setting, App\Event;
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
    <h1>{{ $event->id ? (($mode == 'clone' ? 'Cloning ' : 'Editing ').'"'.$event->name.'"') : 'Add an Event' }}</h1>

    @if($event->id)
        <p><a href="{{ $event->permalink() }}">@icon(arrow-circle-left) {{ $event->name }}</a></p>
    @endif
</div>

@if($mode == 'create' && !$errors->any())
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

@if($errors->any())
    <div class="message is-danger">
        <div class="message-body content">
            <p><b>There was a problem {{ $mode == 'create' ? 'creating' : 'saving' }} the event</b></p>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

<form action="{{ $form_action }}" method="post" class="event-form">

    @if($event->parent)
        <div class="field">
            <p><i>{{ $mode == 'create' ? 'Creating' : 'Editing' }} an event under <b><a href="{{ $event->parent->permalink() }}">{{ $event->parent->name }}</a></b></i></p>
            <input type="hidden" name="parent_id" value="{{ $event->parent->id }}">
        </div>

        <div class="field">
            <div class="control is-expanded">
                <label class="checkbox">
                    <input type="checkbox" name="hide_from_main_feed" value="1" {{ $event->hide_from_main_feed ? 'checked' : '' }}>
                    Hide from main feed (Only show this event on the parent event)
                </label>
            </div>
        </div>

    @endif

    <h2 class="subtitle">What's the name of the event?</h2>

    <div class="field">
        <input class="input @error('name') is-danger @enderror" type="text" autocomplete="off" name="name" value="{{ old('name') ?: $event->name }}" required>
    </div>

    <!-- cover photo will be cropped to 1440x640 -->
    <h2 class="subtitle">Add a cover image (optional)</h2>

    <div id="cover-photo-preview" class="{{ (old('cover_image') ?: $event->cover_image) ? '' : 'hidden' }} has-delete">
        <button class="delete"></button>
        <img src="{{ (old('cover_image') ?: $event->cover_image) }}" width="720" height="320">
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
            <input class="input" type="text" autocomplete="off" name="location_name" value="{{ old('location_name') ?: $event->location_name }}">
        </div>
        <div class="control is-expanded">
            <label class="label">Address</label>
            <input class="input" type="text" autocomplete="off" name="location_address" value="{{ old('location_address') ?: $event->location_address }}">
        </div>
    </div>
    <div class="field is-grouped is-grouped-multiline">
        <div class="control is-expanded">
            <label class="label">City</label>
            <input class="input" type="text" autocomplete="off" name="location_locality" value="{{ old('location_locality') ?: $event->location_locality }}">
        </div>
        <div class="control is-expanded">
            <label class="label">State</label>
            <input class="input" type="text" autocomplete="off" name="location_region" value="{{ old('location_region') ?: $event->location_region }}">
        </div>
        <div class="control is-expanded">
            <label class="label">Country</label>
            <input class="input" type="text" autocomplete="off" name="location_country" value="{{ old('location_country') ?: $event->location_country }}">
        </div>
    </div>




    <h2 class="subtitle">When is the event?</h2>

    <div class="field is-grouped is-grouped-multiline">
        <div class="control is-expanded">
            <label class="label">Start Date</label>
            <input class="input @error('start_date') is-danger @enderror" type="date" name="start_date" autocomplete="off" value="{{ old('start_date') ?: (($mode == 'create' && $event->parent) ? $event->parent->start_date : $event->start_date) }}" required>
        </div>

        <div class="control is-expanded">
            <label class="label">End Date (optional)</label>
            <input class="input" type="date" name="end_date" autocomplete="off" value="{{ old('end_date') ?: $event->end_date }}">
            <div class="help">for multi-day events</div>
        </div>
    </div>

    <div class="field is-grouped is-grouped-multiline" id="time-fields">
        <div class="control is-expanded">
            <label class="label">Start Time <span id="start-time-optional">(optional)</span></label>
            <input class="input" type="time" name="start_time" autocomplete="off" value="{{ old('start_time') ?: $event->start_time }}">
            <div class="help">leave start time blank for multi-day events</div>
        </div>

        <div class="control is-expanded">
            <label class="label">End Time (optional)</label>
            <input class="input" type="time" name="end_time" autocomplete="off" value="{{ old('end_time') ?: $event->end_time }}">
            <div class="help">leave end time blank for multi-day events</div>
        </div>
    </div>

    <div class="field">
        <div class="control is-expanded">
            <label class="label">Timezone <span id="timezone-optional">(optional)</span></label>
            <div class="select is-fullwidth">
                <select name="timezone">
                    @foreach(\App\Event::timezones() as $tz)
                        <option value="{{ $tz }}" {{ (old('timezone') ?: ($event->parent ? $event->parent->timezone : $event->timezone)) == $tz ? 'selected' : '' }} {{ $tz == '──────────' ? 'disabled' : '' }}>{{ $tz }}</option>
                    @endforeach
                </select>
            </div>
            <div class="help">provide a timezone for online events and to help sort events on the same day</div>
        </div>
    </div>

    <h2 class="subtitle">Details</h2>

    <div class="field">
        <label class="label">Website</label>
        <input class="input" type="url" autocomplete="off" name="website" value="{{ old('website') ?: $event->website }}">
        <div class="help">provide a link to the event's main website if any</div>
    </div>

    @if(Setting::value('enable_ticket_url'))
    <div class="field">
        <label class="label">Registration URL</label>
        <input class="input" type="url" autocomplete="off" name="tickets_url" value="{{ old('tickets_url') ?: $event->tickets_url }}">
        <div class="help">if the event requires registration, link to the registration page here. this will also disable RSVPs on this website.</div>
    </div>
    @endif

    <div class="field">
        <label class="label">Code of Conduct</label>
        <input class="input" type="text" autocomplete="off" name="code_of_conduct_url" value="{{ old('code_of_conduct_url') ?: $event->code_of_conduct_url }}">
        <div class="help">provide one or more URLs to codes of conduct that are applicable to this event</div>
    </div>

    @if(Setting::value('zoom_client_id'))
    <div class="field">
        <label class="checkbox">
            <input type="checkbox" name="create_zoom_meeting" value="1">
            Schedule a Zoom Meeting
        </label>
        <div class="help">check the box above to schedule a zoom meeting for this event. the meeting url will be shown on the event page 15 minutes before the start. note: the host will need to log in to zoom as {{ Setting::value('zoom_email') }} to start the meeting.</div>
    </div>
    @endif

    <div class="field" id="meeting-url-field">
        <label class="label">Meeting URL</label>
        <input class="input @error('meeting_url') is-danger @enderror" type="url" autocomplete="off" name="meeting_url" value="{{ old('meeting_url') ?: ($mode == 'clone' ? '' : $event->meeting_url) }}">
        <div class="help">if this is a virtual event, enter a url to join the virtual meeting. <b>this will be shown only 15 minutes before the event start</b>, and hidden afterwards</div>
    </div>

    <div class="field">
        <label class="label">Link to Notes</label>
        <input class="input" type="url" autocomplete="off" name="notes_url" value="{{ old('notes_url') ?: $event->notes_url }}">
        <div class="help">link to etherpad or archived notes for this event</div>
    </div>

    <div class="field">
        <label class="label">Summary</label>
        <textarea class="input" name="summary" style="max-height: none; height: {{ $event->summary ? '15vh' : '15vh' }}">{{ old('summary') ?: $event->summary }}</textarea>
        <div class="help">markdown and HTML are supported</div>
    </div>

    <div class="field">
        <label class="label">Description</label>
        <textarea class="input" name="description" style="max-height: none; height: {{ $event->description ? '75vh' : '25vh' }}">{{ old('description') ?: $event->description }}</textarea>
        <div class="help">markdown and HTML are supported</div>
    </div>

    <div class="field">
        <label class="label">Tags</label>
        <input class="input" type="text" name="tags" value="{{ old('tags') ?: ($event->parent ? $event->parent->tags_string() : $event->tags_string()) }}" autocomplete="off">
        <div class="help">space separated, lowercase</div>
    </div>

    <div class="field" id="video-url-field">
        <label class="label">Video URL</label>
        <input class="input @error('video_url') is-danger @enderror" type="url" autocomplete="off" name="video_url" value="{{ old('video_url') ?: ($mode == 'clone' ? '' : $event->video_url) }}">
        <div class="help">After the event is over, you can add a link to a recording here. YouTube and Vimeo videos will be embedded in the page, otherwise only the link will be displayed.</div>
    </div>

    <div class="field">
        <div class="control is-expanded">
            <label class="label">Status</label>
            <div class="select is-fullwidth">
                <select name="status">
                    @foreach(Event::$STATUSES as $s=>$t)
                    <option value="{{ $s }}" {{ (old('status') ?: $event->status) == $s ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    @if(Setting::value('enable_unlisted_events'))
    <div class="field">
        <div class="control is-expanded">
            <label class="checkbox">
                <input type="checkbox" name="unlisted" value="1" {{ $event->unlisted ? 'checked' : '' }}>
                Unlisted event (prevents this event from showing on the home page and other feeds)
            </label>
        </div>
    </div>
    @endif

    <div class="field">
        <label class="label">Edit Summary</label>
        <input class="input" type="text" name="edit_summary" value="{{ old('edit_summary') }}" autocomplete="off">
        <div class="help">a brief description of your changes</div>
    </div>

    <button class="button is-primary" type="submit" id="save-button">Save</button>

    <input type="hidden" name="latitude" value="{{ old('latitude') ?: $event->latitude }}">
    <input type="hidden" name="longitude" value="{{ old('longitude') ?: $event->longitude }}">
    <input type="hidden" name="cover_image" id="cover-photo-filename" value="{{ old('cover_image') ?: $event->cover_image }}">

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

    $("input[name=start_time]").change();

    $("input[name=end_date]").on('change', function(){
        if($(this).val()) {
            $("#time-fields").addClass('hidden');
        } else {
            $("#time-fields").removeClass('hidden');
        }
    });

    $("input[name=end_date]").change();

    $("input[name=create_zoom_meeting]").click(function(){
        if($(this).is(":checked")) {
            $("#meeting-url-field").addClass('hidden');
            // Require time fields
            $("input[name=start_time]").attr("required","required");
            $("select[name=timezone]").attr("required","required");
            $("#start-time-optional, #timezone-optional").addClass("hidden");
        } else {
            $("#meeting-url-field").removeClass('hidden');
            $("input[name=start_time]").removeAttr("required");
            $("select[name=timezone]").removeAttr("required");
            $("#start-time-optional, #timezone-optional").removeClass("hidden");
        }
    });

    $("input[name=edit_summary]").on('keydown', function(evt){
        if(evt.keyCode == 13) {
            $("#save-button").click();
        }
    });

});
</script>

</section>

@endsection
