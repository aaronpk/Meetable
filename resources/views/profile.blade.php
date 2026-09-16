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
    <label class="label">{{ __('profile.name') }}</label>
    <input class="input" name="name" readonly value="{{ $user->name }}">
  </div>
</div>

@if($user->url)
<div class="field">
  <div class="control">
    <label class="label">{{ __('profile.website') }}</label>
    <input class="input" name="url" readonly value="{{ $user->url }}">
  </div>
</div>
@endif

<div class="notification is-warning">
    {{ __('profile.set_when_logging_in') }}
</div>


@if(env('AUTH_METHOD') == 'vouch')
<form action="{{ route('profile-refresh') }}" method="post" class="settings-form">

    <p class="help">{{ __('profile.refresh_help') }}</p>

    <button class="button is-primary" type="submit">{{ __('profile.refresh') }}</button>

    {{ csrf_field() }}

</form>
@endif


</section>
@endsection
