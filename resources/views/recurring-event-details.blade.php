
<div class="field">
    <div class="control is-expanded">
        <div class="select is-fullwidth">
            <select name="recurrence_interval">
                <option value="weekly_dow">Every Week on {{ $recur_dow }}</option>
                <option value="biweekly_dow">Every Other Week on {{ $recur_dow }}</option>
                <option value="weekly_n">Every N Weeks on {{ $recur_dow }}</option>
                <option value="monthly_date">Every Month on the {{ $recur_date }}</option>
                <option value="yearly">Every Year on {{ $recur_month_date }}</option>
            </select>
        </div>
    </div>
</div>
<div class="field" id="recurrence_count_field" style="display:none;">
    <label class="label">Repeat every how many weeks?</label>
    <div class="control">
        <input type="number" class="input" name="recurrence_interval_count"
               min="1" max="52" value="{{ $event->recurrence_interval_count ?: 3 }}">
    </div>
</div>
