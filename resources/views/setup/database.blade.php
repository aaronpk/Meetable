@extends('setup/layout')

@section('content')
<section class="section">

  <article class="message" style="width: 600px;">
    <div class="message-header">
        <p>Enter your database details</p>
    </div>
    <div class="message-body content">

      @if($message = session('setup-database-error'))
      <article class="message is-danger">
        <div class="message-body">
          {{ $message }}
        </div>
      </article>
      @endif

      <form action="{{ route('setup.test-database') }}" method="post">

        <div class="field">
          <label class="label">Database Name</label>
          <div class="control">
            <input class="input" type="text" placeholder="meetable" value="{{ $db_name ?? 'meetable' }}" name="db_name">
          </div>
        </div>

        <div class="field">
          <label class="label">Database Username</label>
          <div class="control">
            <input class="input" type="text" placeholder="meetable" value="{{ $db_username ?? 'meetable' }}" name="db_username">
          </div>
        </div>

        <div class="field">
          <label class="label">Database Password</label>
          <div class="control">
            <input class="input" type="text" placeholder="password" value="{{ $db_password ?? '' }}" name="db_password">
          </div>
        </div>

        <div class="field">
          <label class="label">Database Host</label>
          <div class="control">
            <input class="input" type="text" value="{{ $db_host ?? '127.0.0.1' }}" name="db_host">
          </div>
        </div>

        <button type="submit" class="button is-primary">Continue</button>

        {{ csrf_field() }}
      </form>
    </div>
  </article>

</section>
@endsection
