@extends('setup/layout')

@section('content')
<section class="section">

  <article class="message" style="width: 600px;">
    <div class="message-header">
      <p>Authentication Method</p>
    </div>
    <div class="message-body content">

      <form action="{{ route('setup.save-auth-method') }}" method="post">

        <p>Choose how you'd like to handle logins to this website:</p>

        <p>The simplest option is to log in with a passkey. Only you will be able to log in, there is no registration flow for additional users at this time. If you would like to allow others to log in, use the GitHub option below.</p>
        <p><button type="submit" name="method" value="session" class="button is-primary">Continue with Passkeys</button></p>

        @if($is_heroku)
        <p>A quick option is to use Heroku login, however this is mainly useful for testing or if you plan on being the only person logging in to edit events.</p>
        <p><button type="submit" class="button is-primary" name="method" value="heroku">Continue with Heroku</button></p>
        @endif

        <p>Using GitHub, you can either let anyone with a GitHub account log in, or decide a specific list of GitHub users who are allowed.</p>
        <p><button type="submit" name="method" value="github" class="button is-primary">Continue with GitHub</button></p>

        {{ csrf_field() }}
      </form>
    </div>
  </article>

</section>
@endsection
