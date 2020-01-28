@extends('setup/layout')

@section('content')
<section class="section">

    <article class="message">
      <div class="message-header">
        <p>Almost done!</p>
      </div>
      <div class="message-body content">
        <p>We were able to save the settings and we're ready to set up the database!</p>

        <a href="/setup/database" class="button is-primary">Create Database</a>
      </div>
    </article>

</section>
@endsection
