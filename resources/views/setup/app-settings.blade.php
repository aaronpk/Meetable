@extends('setup/layout')

@section('content')
<section class="section">

  <article class="message" style="width: 600px;">
    <div class="message-header">
      <p>Application Settings</p>
    </div>
    <div class="message-body content">

      <form action="{{ route('setup.save-app-settings') }}" method="post">

        <div class="field">
          <label class="label">Website Name</label>
          <div class="control">
            <input class="input" type="text" placeholder="Meetable" value="{{ session('setup.app_name', 'Meetable') }}" name="app_name">
          </div>
        </div>

        <div class="field">
          <label class="label">Website URL</label>
          <div class="control">
            <input class="input" type="url" placeholder="{{ isset($_SERVER['HTTPS']) ? 'https' : 'http' }}://{{ $_SERVER['SERVER_NAME'] }}" name="app_url" value="{{ session('setup.app_url') }}">
          </div>
          <p class="help">The base URL of this website, without a trailing slash</p>
        </div>

        <button type="submit" class="button is-primary">Continue</button>

        {{ csrf_field() }}
      </form>
    </div>
  </article>

</section>
<script>
$(function(){
  // Fill in the APP_URL based on what the browser is reporting
  if($("input[name=app_url]").val() == '') {
    $("input[name=app_url]").val(window.location.origin);
  }
});
</script>
@endsection
