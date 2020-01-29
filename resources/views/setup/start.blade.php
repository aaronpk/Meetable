@extends('setup/layout')

@section('content')
<section class="section">

    <article class="message">
      <div class="message-header">
        <p>Welcome to the Meetable Installer</p>
      </div>
      <div class="message-body content">
        <p>Before getting started, we'll need to set up the database. You will need to know the following before proceeding:</p>

        <ul>
            <li>Database name</li>
            <li>Database username</li>
            <li>Database password</li>
            <li>Database host</li>
        </ul>

        @if(\App\Http\Controllers\Setup\Controller::is_heroku())
            <p>At the end, we'll give you a list of <code>heroku</code> commands you can run to configure your app's environment variables.</p>
        @else
            <p>We'll eventually store this information in a config file. If we can't create the config file for any reason, we'll show you the contents of the file and you can create it on your server manually.</p>
        @endif

        <a href="{{ route('setup.database') }}" class="button is-primary">Let's Go</a>
      </div>
    </article>

</section>
@endsection
