@extends('setup/layout')

@section('content')
<section class="section">

    <article class="message">
      <div class="message-header">
        <p>Heroku Configuration</p>
      </div>
      <div class="message-body content">

        <p>Congrats! Everything is ready and you'll be taken to your new website shortly!</p>

        <a href="/" class="button is-primary is-loading">Continue</a>

      </div>
    </article>

</section>
<script>
setTimeout(function(){

    window.location = "/";

}, 8*1000);
</script>
@endsection
