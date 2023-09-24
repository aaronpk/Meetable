@extends('layouts/main')

@section('content')
<section class="section">

    <h2 class="subtitle">Create Admin User</h2>

    <div class="notification is-warning">
        Create your admin account now.
    </div>

    <form action="{{ route('create-user') }}" method="post">

        <div class="field">
          <div class="control">
            <label class="label">Name</label>
            <input class="input" type="text" name="name">
          </div>
        </div>

        <div class="field">
          <div class="control">
            <label class="label">Email</label>
            <input class="input" type="email" name="email">
          </div>
        </div>

        <button class="button is-primary" type="submit">Create User</button>

        {{ csrf_field() }}
    </form>

</section>
@endsection
