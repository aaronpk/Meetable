<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class EventRevision extends Model
{

    protected $casts = [
        'photo_order' => 'array',
    ];

}
