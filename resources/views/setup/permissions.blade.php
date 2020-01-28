@extends('setup/layout')

@section('content')
<section class="section">

    <article class="message is-danger">
      <div class="message-header">
        <p>Welcome to the Meetable Installer</p>
      </div>
      <div class="message-body content">
        <p><b>The web server is unable to write to the storage folder.</b></p>
        <p>Please make sure the entire <code>storage</code> folder and everything inside is writable by the web server before continuing.</p>

        <a href="{{ route('setup.setup') }}" class="button">Try Again</a>
      </div>
    </article>

</section>
@endsection
