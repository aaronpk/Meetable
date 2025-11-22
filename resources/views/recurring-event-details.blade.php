
<div class="field">
    <div class="control is-expanded">
        <div class="select is-fullwidth">
            <select name="recurrence_interval">
                <option value="weekly_dow">Every Week on {{ $recur_dow }}</option>
                <option value="biweekly_dow">Every Other Week on {{ $recur_dow }}</option>
                <option value="monthly_date">Every Month on the {{ $recur_date }}</option>
                <option value="yearly">Every Year on {{ $recur_month_date }}</option>
            </select>
        </div>
    </div>
</div>
