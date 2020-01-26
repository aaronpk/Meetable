@extends('layouts/main')
@php
use App\Setting;
@endphp

@section('content')
<section class="section">

<form action="{{ route('settings-save') }}" method="post" class="settings-form">

    @if($message = session('settings-saved'))
        <article class="message is-primary">
            <div class="message-body">
                {{ $message }}
            </div>
        </article>
    @endif

    <h2 class="title">Configure Website Messages</h2>

    <div class="field">
        <label class="label">Add an Event</label>
        <textarea class="input" name="add_an_event" style="max-height: none; height: 25vh">{{ Setting::value('add_an_event') }}</textarea>
        <div class="help">You can edit the text that appears on the "Add an Event" page for users. Use this to describe what kinds of events should be added to the website. Markdown and HTML are supported.</div>
    </div>

    <button class="button is-primary" type="submit">Save</button>

    {{ csrf_field() }}

</form>

</section>
@endsection
