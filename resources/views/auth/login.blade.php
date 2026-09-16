@extends('layouts/main')

@section('content')
<section class="section">

    <h2 class="subtitle">Log In</h2>

    <div class="notification is-danger hidden" id="error">
        Something went wrong, refresh and try again!
    </div>


    @if(!empty($admin_without_passkey))
    <div class="notification is-warning">
        An admin account doesn't have a passkey yet. To set one up, run <code>php artisan user:passkey-link &lt;email&gt;</code> on the server and open the link it prints.
    </div>
    @endif

    <form id="login-form">
        <button type="submit" class="button is-primary">Log in with a passkey</button>
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
