@extends('setup/layout')

@section('content')
<section class="section">

    <article class="message">
      <div class="message-header">
        <p>Configuration</p>
      </div>
      <div class="message-body content">
        <p>We were unable to automatically create the config file, likely because the folder is not writable by the web server. You will need to copy the below into a file called <code>.env</code> on your server at the root of this project.</p>

        <div class="field">
            <textarea class="textarea is-small" style="font-family: monospace;" rows="12">{{ $config }}</textarea>
        </div>

        <p>Click the button below only after you've created the file.</p>

        <a href="/setup/database" class="button is-primary">Continue</a>
      </div>
    </article>

</section>
@endsection
