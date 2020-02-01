@extends('setup/layout')

@section('content')
<section class="section">

    <article class="message">
      <div class="message-header">
        <p>Heroku Configuration</p>
      </div>
      <div class="message-body content">

        @if($heroku_app)
            <p>You'll need to set the following config entries for the Heroku app. You can run the commands below, paste the values into the application settings manually, or the installer can set these in your Heroku account automatically.</p>
        @else
            <p>You'll need to set the following config entries for the Heroku app. You can run the commands below or paste the values into the application settings manually.</p>
        @endif

        <div class="field">
            <textarea class="textarea is-small" style="font-family: monospace;" rows="12">{{ $config }}</textarea>
        </div>

        @if($heroku_app)
            <a href="{{ route('setup.push-heroku-config') }}" class="button is-primary">Set These For Me</a>
            <a href="{{ route('setup.create-database') }}" class="button">I've Set These Myself</a>
        @else
            <p>Click the button below only after you've finished setting all the config entries.</p>
            <a href="{{ route('setup.create-database') }}" class="button is-primary">I've Set These</a>
        @endif

      </div>
    </article>

</section>
@endsection
