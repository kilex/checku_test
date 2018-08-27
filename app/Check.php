<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Check extends Eloquent
{
    protected $collection = 'checks';
    public $timestamps = false;
}
