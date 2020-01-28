@extends('setup/layout')

@section('content')
<section class="section">

    <article class="message is-success">
      <div class="message-header">
        <p>Success!</p>
      </div>
      <div class="message-body content">
        <p>We've finished creating all the database tables and setup is now complete!</p>

        <a href="/" class="button is-primary">View your Site</a>
      </div>
    </article>

</section>
@endsection
