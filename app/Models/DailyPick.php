<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyPick extends Model
{
    protected $primaryKey = 'date';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];
}
