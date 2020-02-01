@extends('setup/layout')

@section('content')
<section class="section">

  <article class="message" style="width: 600px;">
    <div class="message-header">
      <p>Authentication Method</p>
    </div>
    <div class="message-body content">

      <form action="{{ route('setup.save-auth-method') }}" method="post">

        <p>Choose how you'd like to log in to this website.</p>

        <p>The quickest option is to use Heroku login, however this is mainly useful for testing or if you plan on being the only person logging in to edit events.</p>
        <p><button type="submit" class="button is-primary" name="method" value="heroku">Continue with Heroku</button></p>

        <p>A great option is to use GitHub, so that you can either let anyone with a GitHub account log in, or decide a specific list of GitHub users who are allowed.</p>
        <p><button type="submit" name="method" value="github" class="button is-primary">Continue with GitHub</button></p>

        {{ csrf_field() }}
      </form>
    </div>
  </article>

</section>
@endsection
