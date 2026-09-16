@extends('layouts/main')

@section('content')
<section class="section">

    <div class="notification is-danger">
        <h2 class="subtitle">{{ __('login.errors.title', ['error' => $error]) }}</h2>
        <p>{{ $error_description }}</p>
    </div>

</section>
@endsection
