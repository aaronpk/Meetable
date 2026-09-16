@extends('layouts/main')

@section('content')
<section class="section">

    <h2 class="subtitle">{{ __('login.register.title') }}</h2>

    <div class="notification is-warning">
        {{ __('login.register.intro') }}
    </div>

    <form action="{{ route('create-user') }}" method="post">

        <div class="field">
          <div class="control">
            <label class="label">{{ __('login.register.name') }}</label>
            <input class="input" type="text" name="name">
          </div>
        </div>

        <div class="field">
          <div class="control">
            <label class="label">{{ __('login.register.email') }}</label>
            <input class="input" type="email" name="email">
          </div>
        </div>

        <button class="button is-primary" type="submit">{{ __('login.register.create_user') }}</button>

        {{ csrf_field() }}
    </form>

</section>
@endsection
