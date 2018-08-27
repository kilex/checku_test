<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Cashbox extends Eloquent
{
    protected $collection = 'cashboxes';
    public $timestamps = false;
}
