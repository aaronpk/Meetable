<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * One person's answer, yes, if need be or no, to one candidate date of a proposed event.
 */
class EventDateVote extends Model
{
    protected $hidden = ['id', 'user_id', 'event_date_option_id'];

    public function option() {
        return $this->belongsTo('\App\EventDateOption', 'event_date_option_id');
    }

    public function user() {
        return $this->belongsTo('\App\User', 'user_id');
    }
}
