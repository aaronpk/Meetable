@extends('layouts/main')

@section('content')
<section class="section">

    <div class="notification is-danger">
        <h2 class="subtitle">{{ __('login.errors.title', ['error' => $error]) }}</h2>
        <p>{{ $error_description }}</p>
    </div>

@if(isset($details) && isset($details['error']))
<h3 class="subtitle">{{ __('login.errors.from_oidc_provider') }}</h3>
<pre>{{ $details['error'] }}
{{ $details['error_description'] }}
@endif</pre>

</section>
@endsection
