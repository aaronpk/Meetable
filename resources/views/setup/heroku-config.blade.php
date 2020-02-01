@extends('setup/layout')

@section('content')
<section class="section">

    <article class="message">
      <div class="message-header">
        <p>Heroku Configuration</p>
      </div>
      <div class="message-body content">
        <p>You'll need to set the following config entries for the Heroku app. You can either run the commands below, or paste the values into the application settings manually.</p>

        <div class="field">
            <textarea class="textarea is-small" style="font-family: monospace;" rows="12">{{ $config }}</textarea>
        </div>

        <p>Click the button below only after you've finished setting all the config entries.</p>

        <a href="/setup/database" class="button is-primary">Continue</a>
      </div>
    </article>

</section>
@endsection
