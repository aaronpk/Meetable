@extends('layouts/main')

@section('content')
<section class="section">

    <h2 class="subtitle">Register a Passkey</h2>

    <div class="notification is-warning">
        Register a passkey to protect your account. Setup is not complete until you finish this step!
    </div>

    <form id="register-form">
        <div class="field">
            <label class="label" for="passkey-name">Name this passkey</label>
            <div class="control">
                <input class="input" type="text" id="passkey-name" name="name" value="Passkey" required>
            </div>
        </div>
        <button type="submit" class="button is-primary">Register passkey</button>
    </form>

    <script>
    const register = event => {
        event.preventDefault()

        Passkeys.register(document.getElementById('passkey-name').value)
          .then(response => {
            window.location = '/'
          })
          .catch(error => {
            alert('Something went wrong, try again!')
          })
    }

    document.getElementById('register-form').addEventListener('submit', register)
    </script>

</section>
@endsection
