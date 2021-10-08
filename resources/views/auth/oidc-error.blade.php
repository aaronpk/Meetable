@extends('layouts/main')

@section('content')
<section class="section">

    <div class="notification is-danger">
        <h2 class="subtitle">Error: {{ $error }}</h2>
        <p>{{ $error_description }}</p>
    </div>

@if(isset($details) && isset($details['error']))
<h3 class="subtitle">Error from OpenID Connect Provider</h3>
<pre>{{ $details['error'] }}
{{ $details['error_description'] }}
@endif</pre>

</section>
@endsection
