@extends('setup/layout')

@section('content')
<section class="section">

  <article class="message" style="width: 600px;">
    <div class="message-header">
      <p>Authentication Settings</p>
    </div>
    <div class="message-body content">

      <form action="{{ route('setup.save-auth-settings') }}" method="post">

        <p>By default people will log in to this application with a GitHub account. You will need to go <a href="https://github.com/settings/developers" target="_new">create a GitHub OAuth application</a> and provide the client ID and secret here.</p>

        <div class="field">
          <label class="label">GitHub Client ID</label>
          <div class="control">
            <input class="input" type="text" name="github_client_id" value="{{ session('setup.github_client_id') }}">
          </div>
        </div>

        <div class="field">
          <label class="label">GitHub Client Secret</label>
          <div class="control">
            <input class="input" type="text" name="github_client_secret" value="{{ session('setup.github_client_secret') }}">
          </div>
        </div>

        <div class="field">
          <label class="label">Allowed Users</label>
          <div class="control">
            <input class="input" type="text" name="github_allowed_users" value="{{ session('setup.github_allowed_users') }}">
          </div>
          <p class="help">If you leave this blank, any GitHub user will be able to log in. If you want to restrict logins to certain users, provide a space-separated list of GitHub usernames who will be allowed to log in.</p>
        </div>

        <div class="field">
          <label class="label">Admin Users</label>
          <div class="control">
            <input class="input" type="text" name="github_admin_users" value="{{ session('setup.github_admin_users') }}">
          </div>
          <p class="help">Provide a space-separated list of GitHub usernames for users who you want to be site admins.</p>
        </div>

        <button type="submit" class="button is-primary">Continue</button>

        {{ csrf_field() }}
      </form>
    </div>
  </article>

</section>
@endsection
