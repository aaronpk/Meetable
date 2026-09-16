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
                <span>{{ __('event_form.actions') }}</span>
                <span class="icon is-small">@icon(angle-down)</span>
            </button>
        </div>

        <div class="dropdown-menu" id="dropdown-menu" role="menu">
            <div class="dropdown-content">
                <a class="dropdown-item delete-event" href="{{ route('delete-event', $event) }}">
                    <span class="icon">@icon(trash)</span>
                    <span>{{ __('event_form.delete_event') }}</span>
                </a>
            </div>
        </div>
    </div>

  </div>
</div>
@endif

<div class="content">
    <h1>{{ $event->id ? $action_heading : __('event_form.heading.add') }}</h1>

    @if($event->id && !$event->recurrence_interval)
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
            <p><b>{{ $mode == 'create' ? __('event_form.problem_creating') : __('event_form.problem_saving') }}</b></p>
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
            <p><i>{!! __($mode == 'create' ? 'event_form.creating_under_parent' : 'event_form.editing_under_parent', ['parent' => '<b><a href="'.e($event->parent->permalink()).'">'.e($event->parent->name).'</a></b>']) !!}</i></p>
            <input type="hidden" name="parent_id" value="{{ $event->parent->id }}">
        </div>

        <div class="field">
            <div class="control is-expanded">
                <label class="checkbox">
                    <input type="checkbox" name="hide_from_main_feed" value="1" {{ $event->hide_from_main_feed ? 'checked' : '' }}>
                    {{ __('event_form.hide_from_main_feed') }}
                </label>
            </div>
        </div>

    @endif

    @if($mode == 'recurring' || $event->recurrence_interval)
        <div class="message is-warning">
            <div class="message-body content">
                @if($mode == 'recurring')
                    {{ __('event_form.creating_template') }}
                @else
                    <p>{{ __('event_form.template_explanation') }}</p>
                    <p>{{ __('event_form.template_changes') }}</p>
                @endif
            </div>
        </div>
    @endif

    <h2 class="subtitle">{{ __('event_form.name_question') }}</h2>

    <div class="field">
        <input class="input @error('name') is-danger @enderror" type="text" autocomplete="off" name="name" value="{{ old('name') ?: $event->name }}" required>
    </div>

    <!-- cover photo will be cropped to 1440x640 -->
    <h2 class="subtitle">{{ __('event_form.cover_image_question') }}</h2>

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
                    <span class="file-label">{{ __('event_form.choose_image') }}</span>
                </span>
                <span class="file-name hidden"></span>
            </label>
        </div>
    </div>
    <div class="help">{{ __('event_form.cover_image_help') }}</div>


    <h2 class="subtitle">{{ __('event_form.location_question') }}</h2>

    @if(Setting::value('googlemaps_api_key'))
        <div class="field">
            <div class="dropdown" style="display: block;">
                <div class="dropdown-trigger" style="">
                    <div class="control has-icons-left">
                        <input class="input" type="text" autocomplete="off" name="location" id="location_search" aria-haspopup="true" aria-controls="location_menu" placeholder="{{ __('event_form.search_location') }}">
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
            <label class="label">{{ __('event_form.venue') }}</label>
            <input class="input" type="text" autocomplete="off" name="location_name" value="{{ old('location_name') ?: $event->location_name }}">
        </div>
        <div class="control is-expanded">
            <label class="label">{{ __('event_form.address') }}</label>
            <input class="input" type="text" autocomplete="off" name="location_address" value="{{ old('location_address') ?: $event->location_address }}">
        </div>
    </div>
    <div class="field is-grouped is-grouped-multiline">
        <div class="control is-expanded">
            <label class="label">{{ __('event_form.city') }}</label>
            <input class="input" type="text" autocomplete="off" name="location_locality" value="{{ old('location_locality') ?: $event->location_locality }}">
        </div>
        <div class="control is-expanded">
            <label class="label">{{ __('event_form.state') }}</label>
            <input class="input" type="text" autocomplete="off" name="location_region" value="{{ old('location_region') ?: $event->location_region }}">
        </div>
        <div class="control is-expanded">
            <label class="label">{{ __('event_form.country') }}</label>
            <input class="input" type="text" autocomplete="off" name="location_country" value="{{ old('location_country') ?: $event->location_country }}">
        </div>
    </div>



    @if($mode == 'recurring' || $event->recurrence_interval)
    <h2 class="subtitle">{{ __('event_form.next_occurrence_question') }}</h2>
    @else
    <h2 class="subtitle">{{ __('event_form.when_question') }}</h2>
    @endif

    @php
    // A new event can offer several dates to vote on instead of a date. An event that
    // is already proposed keeps its list of dates until one is chosen on the event page.
    $can_propose = Setting::value('enable_proposed_events') && \Gate::allows('propose-event')
        && $mode != 'recurring' && !$event->recurrence_interval && !$event->is_template;
    $show_propose_checkbox = $can_propose && in_array($mode, ['create', 'clone']);
    $editing_proposal = $mode == 'edit' && $event->is_proposed;
    if($editing_proposal)
        $is_proposed = true;
    elseif($show_propose_checkbox)
        $is_proposed = old('name') !== null ? (bool)old('is_proposed') : (bool)$event->is_proposed;
    else
        $is_proposed = false;

    $option_rows = old('options');
    if(!is_array($option_rows)) {
        $option_rows = [];
        if($event->id && $event->is_proposed) {
            foreach($event->date_options as $option) {
                $option_rows[] = [
                    'id' => $mode == 'edit' ? $option->id : '',
                    'date' => $option->date,
                    'end_date' => $option->end_date,
                    'start_time' => $option->start_time,
                    'end_time' => $option->end_time,
                    'votes' => $mode == 'edit' ? $option->votes()->count() : 0,
                ];
            }
        }
    }
    while(count($option_rows) < 2)
        $option_rows[] = [];
    @endphp

    @if($show_propose_checkbox)
    <div class="field">
        <label class="checkbox">
            <input type="checkbox" name="is_proposed" value="1" id="is_proposed" {{ $is_proposed ? 'checked' : '' }}>
            {{ __('event_form.propose_dates') }}
        </label>
        <div class="help">{{ __('event_form.propose_dates_help') }}</div>
    </div>
    @endif

    <div id="fixed-date-fields" class="{{ $is_proposed ? 'hidden' : '' }}">
    <div class="field is-grouped is-grouped-multiline">
        <div class="control is-expanded">
            <label class="label">{{ __('event_form.start_date') }}</label>
            <input class="input @error('start_date') is-danger @enderror" type="date" name="start_date" autocomplete="off" value="{{ old('start_date') ?: (($mode == 'create' && $event->parent) ? $event->parent->start_date : $event->start_date) }}" required>
        </div>

        @if($mode != 'recurring' && !$event->recurrence_interval)
        <div class="control is-expanded">
            <label class="label">{{ __('event_form.end_date') }}</label>
            <input class="input" type="date" name="end_date" autocomplete="off" value="{{ old('end_date') ?: $event->end_date }}">
            <div class="help">{{ __('event_form.end_date_help') }}</div>
        </div>
        @endif
    </div>

    <div class="field is-grouped is-grouped-multiline" id="time-fields">
        <div class="control is-expanded">
            <label class="label">{{ __('event_form.start_time') }} <span id="start-time-optional">{{ __('event_form.optional') }}</span></label>
            <input class="input" type="time" name="start_time" autocomplete="off" value="{{ old('start_time') ?: $event->start_time }}">
            <div class="help">{{ __('event_form.start_time_help') }}</div>
        </div>

        <div class="control is-expanded">
            <label class="label">{{ __('event_form.end_time') }}</label>
            <input class="input" type="time" name="end_time" autocomplete="off" value="{{ old('end_time') ?: $event->end_time }}">
            <div class="help">{{ __('event_form.end_time_help') }}</div>
        </div>
    </div>
    </div>

    @if($can_propose)
    <div id="proposed-date-fields" class="{{ $is_proposed ? '' : 'hidden' }}">
        @if($editing_proposal)
            <div class="message is-info">
                <div class="message-body">{{ __('event_form.proposed_edit_notice') }}</div>
            </div>
        @endif
        <div class="help">{{ __('event_form.proposed_dates_help') }}</div>

        <div id="proposed-options">
            @foreach($option_rows as $i => $row)
                @include('components/proposed-option-row', ['i' => $i, 'row' => $row])
            @endforeach
        </div>

        <div class="field">
            <button type="button" class="button is-small" id="add-proposed-option">
                <span class="icon">@icon(plus)</span>
                <span>{{ __('event_form.add_another_date') }}</span>
            </button>
        </div>

        <template id="proposed-option-template">
            @include('components/proposed-option-row', ['i' => '__INDEX__', 'row' => []])
        </template>
    </div>
    @endif

    <div class="field">
        <div class="control is-expanded">
            <label class="label">{{ __('event_form.timezone') }} <span id="timezone-optional">{{ __('event_form.optional') }}</span></label>
            <div class="select is-fullwidth">
                <select name="timezone">
                    @foreach(\App\Event::timezones() as $tz)
                        <option value="{{ $tz }}" {{ (old('timezone') ?: ($event->parent ? $event->parent->timezone : $event->timezone)) == $tz ? 'selected' : '' }} {{ $tz == '──────────' ? 'disabled' : '' }}>{{ $tz }}</option>
                    @endforeach
                </select>
            </div>
            <div class="help">{{ __('event_form.timezone_help') }}</div>
        </div>
    </div>

    @if($mode == 'recurring' || $event->recurrence_interval)
    <h2 class="subtitle">{{ __('event_form.repeat_question') }}</h2>

    <div id="recurring_details">
    </div>

    <script>
    $(function(){
        var selected_recurrence_interval;
        var selected_recurrence_interval_count;
        function toggle_count() {
            if($("select[name=recurrence_interval]").val() == 'weekly_n')
                $("#recurrence_count_field").show();
            else
                $("#recurrence_count_field").hide();
        }
        function reload_recurrence_menu() {
            $.post("/event/{{ $event->id }}/recurring/details", {
                _token: csrf_token(),
                date: $("input[name=start_date]").val()
            }, function(response) {
                $("#recurring_details").html(response);
                var $interval = $("select[name=recurrence_interval]");
                if(selected_recurrence_interval) {
                    $interval.val(selected_recurrence_interval);
                } else {
                    $interval.val("{{ $event->recurrence_interval ?: 'weekly_dow' }}");
                }

                // The options depend on the date, so a previously chosen schedule
                // may not be offered any more. Fall back to the first one.
                if($interval.prop("selectedIndex") < 0)
                    $interval.prop("selectedIndex", 0);

                if(selected_recurrence_interval_count) {
                    $("input[name=recurrence_interval_count]").val(selected_recurrence_interval_count);
                }
                toggle_count();

                $("select[name=recurrence_interval]").on('change', function(){
                    selected_recurrence_interval = $(this).val();
                    toggle_count();
                });

                $("input[name=recurrence_interval_count]").on('change', function(){
                    selected_recurrence_interval_count = $(this).val();
                });
            });
        }
        reload_recurrence_menu();
        $("input[name=start_date]").on('change', reload_recurrence_menu);
    });
    </script>
    <input type="hidden" name="is_template" value="1">
    <input type="hidden" name="created_from_template_event_id" value="{{ $event->id }}">
    @endif

    <h2 class="subtitle">{{ __('event_form.details') }}</h2>

    <div class="field">
        <label class="label">{{ __('event_form.website') }}</label>
        <input class="input" type="url" autocomplete="off" name="website" value="{{ old('website') ?: $event->website }}">
        <div class="help">{{ __('event_form.website_help') }}</div>
    </div>

    @if(Setting::value('enable_ticket_url'))
    <div class="field">
        <label class="label">{{ __('event_form.registration_url') }}</label>
        <input class="input" type="url" autocomplete="off" name="tickets_url" value="{{ old('tickets_url') ?: $event->tickets_url }}">
        <div class="help">{{ __('event_form.registration_url_help') }}</div>
    </div>
    @endif

    <div class="field">
        <label class="label">{{ __('event_form.code_of_conduct') }}</label>
        <input class="input" type="text" autocomplete="off" name="code_of_conduct_url" value="{{ old('code_of_conduct_url') ?: $event->code_of_conduct_url }}">
        <div class="help">{{ __('event_form.code_of_conduct_help') }}</div>
    </div>

    @if(Setting::value('zoom_client_id'))
    <div class="field" id="zoom-field">
        <label class="checkbox">
            <input type="checkbox" name="create_zoom_meeting" value="1">
            {{ __('event_form.schedule_zoom') }}
        </label>
        <div class="help">{{ __('event_form.schedule_zoom_help', ['email' => Setting::value('zoom_email')]) }}</div>
    </div>
    @endif

    <div class="field" id="meeting-url-field">
        <label class="label">{{ __('event_form.meeting_url') }}</label>
        <input class="input @error('meeting_url') is-danger @enderror" type="url" autocomplete="off" name="meeting_url" value="{{ old('meeting_url') ?: $event->meeting_url }}">
        <div class="help">{!! __('event_form.meeting_url_help', ['shown_when' => '<b>'.e(__('event_form.meeting_url_help_shown_when')).'</b>']) !!}</div>
    </div>

    <div class="field">
        <label class="label">{{ __('event_form.notes_url') }}</label>
        <input class="input" type="url" autocomplete="off" name="notes_url" value="{{ old('notes_url') ?: $event->notes_url }}">
        <div class="help">{{ __('event_form.notes_url_help') }}</div>
    </div>

    <div class="field">
        <label class="label">{{ __('event_form.summary') }}</label>
        <textarea class="input" name="summary" style="max-height: none; height: {{ $event->summary ? '15vh' : '15vh' }}">{{ old('summary') ?: $event->summary }}</textarea>
        <div class="help">{{ __('event_form.markdown_supported') }}</div>
    </div>

    <div class="field">
        <label class="label">{{ __('event_form.description') }}</label>
        <textarea class="input" name="description" style="max-height: none; height: {{ $event->description ? '75vh' : '25vh' }}">{{ old('description') ?: $event->description }}</textarea>
        <div class="help">{{ __('event_form.markdown_supported') }}</div>
    </div>

    <div class="field">
        <label class="label">{{ __('event_form.tags') }}</label>
        <input class="input" type="text" name="tags" value="{{ old('tags') ?: ($event->parent ? $event->parent->tags_string() : $event->tags_string()) }}" autocomplete="off">
        <div class="help">{{ __('event_form.tags_help') }}</div>
    </div>

    <div class="field" id="video-url-field">
        <label class="label">{{ __('event_form.video_url') }}</label>
        <input class="input @error('video_url') is-danger @enderror" type="url" autocomplete="off" name="video_url" value="{{ old('video_url') ?: ($mode == 'clone' ? '' : $event->video_url) }}">
        <div class="help">{{ __('event_form.video_url_help') }}</div>
    </div>

    <div class="field" id="status-field">
        <div class="control is-expanded">
            <label class="label">{{ __('event_form.status') }}</label>
            <div class="select is-fullwidth">
                <select name="status">
                    @foreach(Event::$STATUSES as $s=>$t)
                    <option value="{{ $s }}" {{ (old('status') ?: $event->status) == $s ? 'selected' : '' }}>{{ Event::status_label($s) }}</option>
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
                {{ __('event_form.unlisted') }}
            </label>
        </div>
    </div>
    @endif

    <div class="field">
        <label class="label">{{ __('event_form.edit_summary') }}</label>
        <input class="input" type="text" name="edit_summary" value="{{ old('edit_summary') }}" autocomplete="off">
        <div class="help">{{ __('event_form.edit_summary_help') }}</div>
    </div>

    <button class="button is-primary" type="submit" id="save-button">{{ __('common.save') }}</button>

    <input type="hidden" name="latitude" value="{{ old('latitude') ?: $event->latitude }}">
    <input type="hidden" name="longitude" value="{{ old('longitude') ?: $event->longitude }}">
    <input type="hidden" name="cover_image" id="cover-photo-filename" value="{{ old('cover_image') ?: $event->cover_image }}">
    @if($mode == 'clone')
        <input type="hidden" name="cloned_from_id" value="{{ $event->id }}">
        <input type="hidden" name="previous_instance_date" value="{{ $event->start_date }}">
    @endif

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

    function update_zoom_fields() {
        if($("input[name=create_zoom_meeting]").is(":checked")) {
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
    }

    $("input[name=create_zoom_meeting]").click(update_zoom_fields);

    // Proposing dates instead of setting one. The fields of the block that isn't in use
    // are disabled so they are neither validated by the browser nor submitted.
    var nextOptionIndex = $("#proposed-options .proposed-option").length;

    function is_proposed() {
        return $("#is_proposed").length ? $("#is_proposed").is(":checked") : $("#proposed-date-fields").length > 0;
    }

    function toggle_proposed() {
        var proposed = is_proposed();
        $("#fixed-date-fields").toggleClass("hidden", proposed).find(":input").prop("disabled", proposed);
        $("#proposed-date-fields").toggleClass("hidden", !proposed).find(":input").prop("disabled", !proposed);
        $("#status-field").toggleClass("hidden", proposed).find(":input").prop("disabled", proposed);
        $("#zoom-field").toggleClass("hidden", proposed);
        if(proposed) {
            $("input[name=create_zoom_meeting]").prop("checked", false);
            update_zoom_fields();
        }
    }

    $("#is_proposed").on("change", toggle_proposed);
    if($("#proposed-date-fields").length) {
        toggle_proposed();
    }

    $("#add-proposed-option").click(function(){
        var html = $("#proposed-option-template").html().replace(/__INDEX__/g, nextOptionIndex++);
        $("#proposed-options").append(html);
    });

    // A candidate date with an end date is a multi-day event, which has no times
    $("#proposed-options").on("change", ".option-end-date", function(){
        var $row = $(this).closest(".proposed-option");
        $row.find(".option-time").toggleClass("hidden", !!$(this).val());
    });

    $("#proposed-options").on("click", ".remove-proposed-option", function(){
        var $row = $(this).closest(".proposed-option");
        var votes = parseInt($row.attr("data-votes") || 0, 10);
        if(votes > 0 && !confirm(lang("remove_option_with_votes", {count: votes}))) {
            return;
        }
        $row.remove();
    });

    $("form.event-form").on("submit", function(evt){
        if(!is_proposed()) {
            return;
        }
        var filled = $("#proposed-options input[type=date]").filter(function(){ return $(this).val(); }).length;
        if(filled < 2) {
            alert(lang("proposed_needs_two_dates"));
            evt.preventDefault();
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
