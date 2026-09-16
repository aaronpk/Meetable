@extends('layouts/main')

@section('content')
<section class="section">

    <style>
        form {
            max-width: 75ch;
        }
    </style>

    @if(isset($error))
        <div class="notification is-danger">
            {{ $error }}
        </div>
    @endif

    <form action="{{ route('webmention') }}" method="post">

        <div class="field">
            <label class="label">{{ __('responses.webmention.your_url') }}</label>
            <input class="input" type="url" autocomplete="off" name="source" value="{{ $source ?? '' }}">
        </div>

        <div class="field">
            <label class="label">{{ __('responses.webmention.event_url') }}</label>
            <input class="input" type="url" autocomplete="off" name="target">
        </div>

        <button class="button is-primary" type="submit">{{ __('responses.webmention.send') }}</button>
        <input type="hidden" name="from" value="browser">

        {{ csrf_field() }}
    </form>

</section>
@endsection
