@extends('setup/layout')

@section('content')
<section class="section">

  <article class="message" style="width: 600px;">
    <div class="message-header">
      <p>Authentication Settings</p>
    </div>
    <div class="message-body content">

      <form action="{{ route('setup.save-auth-settings') }}" method="post">

        @if(session('setup.auth_method') == 'heroku')

            @if(preg_match('/\.herokuapp\.com/', session('setup.app_url')))
                <p>Provide your own Heroku OAuth application credentials, or use the button below to register an application automatically.</p>
                <p>If you register your own Heroku app, use the following as the redirect URL when creating it:</p>
                <p><code>{{ session('setup.app_url') }}/auth/heroku</code></p>
                <p><button type="button" class="button" id="create-heroku-app">Register</button></p>
                <p id="heroku-regsiter-error"></p>
            @else
                <p>You'll need to go register an OAuth client at Heroku and provide the client ID and secret here. Use the following as the redirect URL when registering the app:</p>
                <p><code>{{ session('setup.app_url') }}/auth/heroku</code></p>
            @endif

        <div class="field">
          <label class="label">Heroku Client ID</label>
          <div class="control">
            <input class="input" type="text" name="heroku_client_id" value="{{ session('setup.heroku_client_id') }}">
          </div>
        </div>

        <div class="field">
          <label class="label">Heroku Client Secret</label>
          <div class="control">
            <input class="input" type="text" name="heroku_client_secret" value="{{ session('setup.heroku_client_secret') }}">
          </div>
        </div>

        @else

        <p>To be able to log in to the site, you'll need a GitHub account and a GitHub OAuth application. You will need to go <a href="https://github.com/settings/developers" target="_new">create a GitHub OAuth application</a> and provide the client ID and secret here.</p>
        <p>In the GitHub application settings, use the following as the callback URL in the application settings:</p>
        <p><code>{{ session('setup.app_url') }}/auth/github</code></p>

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
          <p class="help">Provide a space-separated list of GitHub usernames for users who you want to be site admins. Make sure to include yourself in this list!</p>
        </div>

        @endif

        <button type="submit" class="button is-primary">Continue</button>

        {{ csrf_field() }}
      </form>
    </div>
  </article>

</section>
<script>
$(function(){
    $("#create-heroku-app").click(function(){
        $(this).addClass('is-loading');
        $.post("{{ route('setup.register-heroku-app') }}", {
            _token: $("input[name=_token]").val()
        }, function(response){
            $("#create-heroku-app").removeClass('is-loading');
            if(response.client_id) {
                $("input[name=heroku_client_id").val(response.client_id);
                $("input[name=heroku_client_secret").val(response.client_secret);
                $("button[type=submit]").click();
            } else {
                $("#heroku-regsiter-error").text("There was a problem registering the app automatically. Please register manually and provide the details below.");
                $("#create-heroku-app").addClass('hidden');
            }
        });
    });
});
</script>
@endsection
