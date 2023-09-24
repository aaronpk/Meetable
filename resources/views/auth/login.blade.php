@extends('layouts/main')

@section('content')
<section class="section">

    <h2 class="subtitle">Log In</h2>

    <div class="notification is-danger hidden" id="error">
        Something went wrong, refresh and try again!
    </div>


    <form id="login-form">
        <button type="submit" class="button is-primary">Log in with a passkey</button>
    </form>

    <script>
        const login = event => {
            event.preventDefault()

            new WebAuthn().login({
            }, {
                remember: null,
            }).then(response => {
                window.location = "/"
            })
            .catch(error => {
                $("#error").removeClass("hidden")
            })
        }

        document.getElementById('login-form').addEventListener('submit', login)
    </script>

</section>
@endsection
