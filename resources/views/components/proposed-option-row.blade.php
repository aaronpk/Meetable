{{-- One candidate date in the event form. $i is the row's index in options[], $row its values.
     Also rendered inside a <template> with $i = __INDEX__ for rows added on the page.
     Like a scheduled event, a candidate with an end date is a multi-day event and has no times. --}}
<div class="proposed-option" data-votes="{{ $row['votes'] ?? 0 }}">
    <input type="hidden" name="options[{{ $i }}][id]" value="{{ $row['id'] ?? '' }}">

    <button type="button" class="button is-small is-light remove-proposed-option" title="{{ __('event_form.remove_date') }}" aria-label="{{ __('event_form.remove_date') }}">
        <span class="icon">@icon(times)</span>
    </button>

    <div class="field is-grouped is-grouped-multiline">
        <div class="control is-expanded">
            <label class="label">{{ __('event_form.start_date') }}</label>
            <input class="input" type="date" name="options[{{ $i }}][date]" value="{{ $row['date'] ?? '' }}" autocomplete="off" required>
        </div>
        <div class="control is-expanded">
            <label class="label">{{ __('event_form.end_date') }}</label>
            <input class="input option-end-date" type="date" name="options[{{ $i }}][end_date]" value="{{ $row['end_date'] ?? '' }}" autocomplete="off">
            <div class="help">{{ __('event_form.end_date_help') }}</div>
        </div>
    </div>

    <div class="field is-grouped is-grouped-multiline option-time {{ !empty($row['end_date']) ? 'hidden' : '' }}">
        <div class="control is-expanded">
            <label class="label">{{ __('event_form.start_time') }} <span>{{ __('event_form.optional') }}</span></label>
            <input class="input" type="time" name="options[{{ $i }}][start_time]" value="{{ $row['start_time'] ?? '' }}" autocomplete="off">
        </div>
        <div class="control is-expanded">
            <label class="label">{{ __('event_form.end_time') }}</label>
            <input class="input" type="time" name="options[{{ $i }}][end_time]" value="{{ $row['end_time'] ?? '' }}" autocomplete="off">
        </div>
    </div>

    @if(!empty($row['votes']))
        <div class="help option-votes">{{ trans_choice('event_form.option_votes', $row['votes'], ['count' => $row['votes']]) }}</div>
    @endif
</div>
