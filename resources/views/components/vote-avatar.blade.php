{{-- A small picture of a user who voted on a candidate date, with their name in a tooltip.
     The tooltip goes on a wrapper because it is drawn with pseudo-elements, which images can't have. --}}
@php
$photo = $user->photo ? \App\Helpers\Uri::safe_href($user->photo) : '/images/placeholder.png';
@endphp
@if($user->url)
    <a href="{{ \App\Helpers\Uri::safe_href($user->url) }}" class="vote-avatar-link has-tooltip-bottom" data-tooltip="{{ $user->display_name() }}"><img src="{{ $photo }}" class="vote-avatar" width="20" height="20" alt="{{ $user->display_name() }}"></a>
@else
    <span class="vote-avatar-link has-tooltip-bottom" data-tooltip="{{ $user->display_name() }}"><img src="{{ $photo }}" class="vote-avatar" width="20" height="20" alt="{{ $user->display_name() }}"></span>
@endif
