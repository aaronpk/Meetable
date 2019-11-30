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
            <label class="label">Your URL</label>
            <input class="input" type="url" autocomplete="off" name="source" value="{{ $source ?? '' }}">
        </div>

        <div class="field">
            <label class="label">Event URL</label>
            <input class="input" type="url" autocomplete="off" name="target">
        </div>

        <button class="button is-primary" type="submit">Send Webmention</button>

        {{ csrf_field() }}
    </form>

</section>
@endsection
