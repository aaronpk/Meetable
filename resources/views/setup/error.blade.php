@extends('setup/layout')

@section('content')
<section class="section">

    <article class="message is-danger">
      <div class="message-header">
        <p>Error!</p>
      </div>
      <div class="message-body content">
        <p>Something went wrong during the setup. You can start over and try again, or if you continue having trouble, try creating the config file by hand by copying <code>.env.template</code> to <code>.env</code> and filling out the details there.</p>

        <a href="/" class="button">Start Over</a>
      </div>
    </article>

</section>
@endsection
