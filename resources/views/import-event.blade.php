@extends('layouts/main')

@section('content')
<section class="section">

<form action="{{ $form_action }}" method="get" class="event-form">

    <h2 class="subtitle">Import an Event</h2>

    <div class="field">
        <input class="input" type="url" autocomplete="off" name="url" required>
        <div class="help">Enter a URL to import that event, such as an event on another Meetable instance. Currently only Microformats is supported.</div>
    </div>

    <button class="button is-primary" type="submit">Preview</button>
</form>

</section>
@endsection
