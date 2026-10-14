{{-- The candidate dates of a proposed event and the votes on them.
     $options    the candidate dates, from Event::date_options_with_tallies()
     $readonly   true once a date has been chosen: no vote buttons or schedule buttons
     $leading_id the option in the lead, or null
     $chosen_id  the option that was chosen, or null
     $user_votes the logged-in user's answers as [option id => vote] --}}
@php
$vote_labels = [
    'yes' => __('events.proposed.yes'),
    'ifneedbe' => __('events.proposed.ifneedbe'),
    'no' => __('events.proposed.no'),
];
$vote_icons = ['yes' => 'check', 'ifneedbe' => 'question', 'no' => 'times'];
@endphp
<div class="date-poll" id="date-poll" data-action="{{ route('event-date-vote', $event->id) }}">
<div class="table-container">
<table class="table is-fullwidth">
    <thead>
        <tr>
            <th>{{ __('events.proposed.date_column') }}</th>
            @foreach($vote_labels as $vote => $label)
                <th class="count">{{ $label }}</th>
            @endforeach
            @if(!$readonly)
                <th></th>
            @endif
        </tr>
    </thead>
    <tbody>
    @foreach($options as $option)
        <tr class="poll-option {{ $option->id == $leading_id ? 'is-leading' : '' }} {{ $option->id == $chosen_id ? 'is-chosen' : '' }}" data-option-id="{{ $option->id }}">
            <td class="option-date">
                <b>{{ $option->display_date() }}</b>
                @if($option->start_time)
                    <div class="time">{{ $option->display_time() }}</div>
                @endif
                <span class="tag is-warning is-light leading-tag">{{ __('events.proposed.leading') }}</span>
                @if($option->id == $chosen_id)
                    <span class="tag is-success">{{ __('events.proposed.chosen') }}</span>
                @endif
                @if(!$readonly)
                    @can('manage-event', $event)
                    <form method="post" action="{{ route('finalize-event', $event->id) }}" class="finalize-form" data-date="{{ $option->display_date() }}{{ $option->start_time ? ', '.$option->display_time() : '' }}">
                        {{ csrf_field() }}
                        <input type="hidden" name="option_id" value="{{ $option->id }}">
                        <button class="button is-small is-primary is-light">
                            <span class="icon">@icon(calendar-check)</span>
                            <span>{{ __('events.proposed.schedule_this_date') }}</span>
                        </button>
                    </form>
                    @endcan
                @endif
            </td>
            @foreach($vote_labels as $vote => $label)
                <td class="count count-{{ $vote }}" data-label="{{ $label }}">
                    <span class="number">{{ $option->vote_counts()[$vote] }}</span>
                    <div class="voters">
                        @foreach($option->voters($vote) as $voter)
                            @include('components/vote-avatar', ['user' => $voter])
                        @endforeach
                    </div>
                </td>
            @endforeach
            @if(!$readonly)
                <td class="my-vote">
                    @can('can-vote')
                    <div class="buttons has-addons">
                        @foreach($vote_labels as $vote => $label)
                            <button class="button is-small vote-button is-{{ $vote }} {{ ($user_votes[$option->id] ?? null) == $vote ? 'is-pressed' : '' }}" data-vote="{{ $vote }}" title="{{ $label }}" aria-label="{{ $label }}">
                                <span class="icon"><svg class="svg-icon"><use xlink:href="/font-awesome-5.11.2/sprites/solid.svg#{{ $vote_icons[$vote] }}"></use></svg></span>
                                <span class="is-hidden-tablet">{{ $label }}</span>
                            </button>
                        @endforeach
                    </div>
                    @endcan
                </td>
            @endif
        </tr>
    @endforeach
    </tbody>
</table>
</div>

@if(!$readonly)
    @can('manage-event', $event)
    {{-- Asks before a date is chosen, since that closes the vote. Opened by the schedule buttons above. --}}
    <div class="modal" id="finalize-modal">
        <div class="modal-background"></div>
        <div class="modal-card">
            <header class="modal-card-head">
                <p class="modal-card-title">{{ __('events.proposed.schedule_confirm_title') }}</p>
                <button class="delete finalize-cancel" aria-label="{{ __('common.close') }}"></button>
            </header>
            <section class="modal-card-body">
                <p>{!! __('events.proposed.schedule_confirm', ['date' => '<b class="finalize-date"></b>']) !!}</p>
            </section>
            <footer class="modal-card-foot">
                <div class="buttons">
                    <button class="button is-primary finalize-confirm">
                        <span class="icon">@icon(calendar-check)</span>
                        <span>{{ __('events.proposed.schedule_this_date') }}</span>
                    </button>
                    <button class="button finalize-cancel">{{ __('common.cancel') }}</button>
                </div>
            </footer>
        </div>
    </div>
    @endcan

    @guest
        @if(!\App\Setting::value('auth_hide_login'))
            <p class="help"><a href="{{ route('login') }}">{{ __('events.proposed.log_in_to_vote') }}</a></p>
        @endif
    @else
        @cannot('can-vote')
            <p class="help">{{ __('events.proposed.voting_closed') }}</p>
        @endcannot
    @endguest
@endif
</div>
