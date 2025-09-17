@extends('layouts/main')

@section('content')
<section class="section">

    <div class="notification is-danger">
        <h2 class="subtitle">Error: {{ $error }}</h2>
        <p>{{ $error_description }}</p>
    </div>

</section>
@endsection
