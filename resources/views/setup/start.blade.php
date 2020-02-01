@extends('setup/layout')

@section('content')
<section class="section">

    <article class="message">
      <div class="message-header">
        <p>Welcome to the Meetable Installer</p>
      </div>
      <div class="message-body content">
        @if(env('DATABASE_URL'))
            <p>We're going to ask you a few questions to get started!</p>
            <form action="{{ route('setup.test-database') }}" method="post">
                <button type="submit" class="button is-primary">Let's Go</button>
                {{ csrf_field() }}
            </form>
        @else
            @if(\App\Http\Controllers\Setup\Controller::is_heroku())
                <p><b>Please make sure you've already created a MySQL database</b></p>
                <p>The easiest way is to use the ClearDB add-on in heroku, which you can add to your project from the command line:</p>
                <p><code>heroku addons:create cleardb:punch</code></p>
                <p>If you are using a different database, you'll need to have the database connection info ready in the next step.</p>

                <p>At the end, we'll give you a list of Heroku config settings to configure your application.</p>
            @else
                <p>Before getting started, we'll need to set up the database. You will need to know the following before proceeding:</p>

                <ul>
                    <li>Database name</li>
                    <li>Database username</li>
                    <li>Database password</li>
                    <li>Database host</li>
                </ul>

                <p>We'll eventually store this information in a config file. If we can't create the config file for any reason, we'll show you the contents of the file and you can create it on your server manually.</p>
            @endif

            <a href="{{ route('setup.database') }}" class="button is-primary">Let's Go</a>
        @endif
      </div>
    </article>

</section>
@endsection
