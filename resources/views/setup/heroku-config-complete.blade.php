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
function checkIfComplete() {
    setTimeout(function(){
        $.get('/setup/heroku-in-progress', function(response){
            if(response.setup == 'finished') {
                setTimeout(function(){
                    window.location = "/";
                }, 500);
            } else {
                checkIfComplete();
            }
        });
    }, 2000);
}
checkIfComplete();
</script>
@endsection
