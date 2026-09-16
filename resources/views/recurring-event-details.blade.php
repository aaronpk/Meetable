
<div class="field">
    <div class="control is-expanded">
        <div class="select is-fullwidth">
            <select name="recurrence_interval">
                <option value="weekly_dow">{{ __('recurrence.options.weekly', ['weekday' => $recur_dow]) }}</option>
                <option value="biweekly_dow">{{ __('recurrence.options.biweekly', ['weekday' => $recur_dow]) }}</option>
                <option value="weekly_n">{{ __('recurrence.options.every_n_weeks', ['weekday' => $recur_dow]) }}</option>
                <option value="monthly_date">{{ __('recurrence.options.monthly_date', ['day' => $recur_date]) }}</option>
                <option value="monthly_dow">{{ __('recurrence.options.monthly_dow', ['position' => $recur_dow_ordinal]) }}</option>
                @if($recur_dow_from_end)
                <option value="monthly_dow_last">{{ __('recurrence.options.monthly_dow', ['position' => $recur_dow_from_end]) }}</option>
                @endif
                <option value="yearly">{{ __('recurrence.options.yearly', ['date' => $recur_month_date]) }}</option>
            </select>
        </div>
    </div>
</div>
<div class="field" id="recurrence_count_field" style="display:none;">
    <label class="label">{{ __('recurrence.options.how_many_weeks') }}</label>
    <div class="control">
        <input type="number" class="input" name="recurrence_interval_count"
               min="1" max="52" value="{{ $event->recurrence_interval_count ?: 3 }}">
    </div>
</div>
