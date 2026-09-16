{{-- A small picture of a user who voted on a candidate date --}}
@php
$photo = $user->photo ? \App\Helpers\Uri::safe_href($user->photo) : '/images/placeholder.png';
@endphp
@if($user->url)
    <a href="{{ \App\Helpers\Uri::safe_href($user->url) }}"><img src="{{ $photo }}" class="vote-avatar" width="20" height="20" alt="{{ $user->display_name() }}" title="{{ $user->display_name() }}"></a>
@else
    <img src="{{ $photo }}" class="vote-avatar" width="20" height="20" alt="{{ $user->display_name() }}" title="{{ $user->display_name() }}">
@endif
