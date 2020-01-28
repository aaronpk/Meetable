@extends('setup/layout')

@section('content')
<section class="section">

    <article class="message">
      <div class="message-header">
        <p>Heroku Configuration</p>
      </div>
      <div class="message-body content">
        <p>You'll need to run the following commands in order to configure the application in Heroku</p>

        <div class="field">
            <textarea class="textarea is-small" style="font-family: monospace;" rows="12">{{ $config }}</textarea>
        </div>

        <p>Click the button below only after you've run all the commands above.</p>

        <a href="/setup/database" class="button is-primary">Continue</a>
      </div>
    </article>

</section>
@endsection
