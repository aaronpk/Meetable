@extends('layouts/main')

@section('content')
<section class="section">


@php
$user = Auth::user();
@endphp

<div class="field">
  <div class="control">
    <img src="{{ $user->photo }}" width="120" style="border: 1px #ccc solid; border-radius: 4px;">
  </div>
</div>

<div class="field">
  <div class="control">
    <label class="label">Name</label>
    <input class="input" name="name" readonly value="{{ $user->name }}">
  </div>
</div>

@if($user->url)
<div class="field">
  <div class="control">
    <label class="label">Website</label>
    <input class="input" name="url" readonly value="{{ $user->url }}">
  </div>
</div>
@endif

<div class="notification is-warning">
    Your profile information is set when you log in.
</div>


@if(env('AUTH_METHOD') == 'discord')
<form action="{{ route('profile-refresh') }}" method="post" class="settings-form">

    <p class="help">Click the button below to re-fetch your profile info from your website.</p>

    <button class="button is-primary" type="submit">Fetch Profile Info</button>

    {{ csrf_field() }}

</form>
@endif


</section>
@endsection
