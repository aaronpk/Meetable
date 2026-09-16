@extends('layouts/main')

@section('content')
<section class="section">

    <h2 class="subtitle">{{ __('login.log_in') }}</h2>

    <div class="notification is-danger hidden" id="error">
        {{ __('login.passkey_login_failed') }}
    </div>


    @if(!empty($admin_without_passkey))
    <div class="notification is-warning">
        {!! __('login.admin_without_passkey', ['command' => '<code>php artisan user:passkey-link &lt;email&gt;</code>']) !!}
    </div>
    @endif

    <form id="login-form">
        <button type="submit" class="button is-primary">{{ __('login.log_in_with_passkey') }}</button>
    </form>

    <script>
        const login = event => {
            event.preventDefault()

            Passkeys.login()
              .then(response => {
                window.location = response.redirect || "/"
              })
              .catch(error => {
                $("#error").removeClass("hidden")
              })
        }

        document.getElementById('login-form').addEventListener('submit', login)
    </script>

</section>
@endsection
