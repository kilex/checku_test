<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Cashbox;

class CashboxController extends Controller
{
    public function index()
    {
        return Cashbox::all();
    }
}
