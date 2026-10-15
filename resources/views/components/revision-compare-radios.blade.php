{{-- The two radio buttons that pick a revision as the start or end of a comparison.
     $position is 0 for the newest revision, which starts selected as the end, with the
     next one as the start. --}}
<span class="compare">
    <input type="radio" name="from" value="{{ $revision->id }}" {{ $position == 1 ? 'checked' : '' }} aria-label="{{ __('revisions.compare_from') }}" title="{{ __('revisions.compare_from') }}">
    <input type="radio" name="to" value="{{ $revision->id }}" {{ $position == 0 ? 'checked' : '' }} aria-label="{{ __('revisions.compare_to') }}" title="{{ __('revisions.compare_to') }}">
</span>
