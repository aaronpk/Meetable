<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DiscordNotificationSend extends Model
{
    public function notification() {
        return $this->belongsTo('\App\DiscordNotification', 'discord_notification_id');
    }

    public function event() {
        return $this->belongsTo('\App\Event');
    }
}
