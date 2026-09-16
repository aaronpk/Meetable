@extends('layouts/main')

@section('content')
<section class="section">

    <h2 class="subtitle">{{ __('login.passkey.title') }}</h2>

    <div class="notification is-warning">
        {{ __('login.passkey.intro') }}
    </div>

    <form id="register-form">
        <div class="field">
            <label class="label" for="passkey-name">{{ __('login.passkey.name_label') }}</label>
            <div class="control">
                <input class="input" type="text" id="passkey-name" name="name" value="{{ __('login.passkey.default_name') }}" required>
            </div>
        </div>
        <button type="submit" class="button is-primary">{{ __('login.passkey.register') }}</button>
    </form>

    <script>
    const register = event => {
        event.preventDefault()

        Passkeys.register(document.getElementById('passkey-name').value)
          .then(response => {
            window.location = '/'
          })
          .catch(error => {
            alert(@json(__('login.passkey.register_failed')))
          })
    }

    document.getElementById('register-form').addEventListener('submit', register)
    </script>

</section>
@endsection
