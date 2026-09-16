@extends('layouts/main')

@section('content')
<section class="section">

<form action="{{ $form_action }}" method="get" class="event-form">

    <h2 class="subtitle">{{ __('event_form.import.title') }}</h2>

    <div class="field">
        <input class="input" type="url" autocomplete="off" name="url" required>
        <div class="help">{{ __('event_form.import.help') }}</div>
    </div>

    <button class="button is-primary" type="submit">{{ __('event_form.import.preview') }}</button>
</form>

</section>
@endsection
