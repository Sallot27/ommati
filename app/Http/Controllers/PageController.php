<?php

namespace App\Http\Controllers;

class PageController extends Controller
{
    public function tasbih()
    {
        return view('tasbih');
    }

    public function more()
    {
        return view('more');
    }

    public function install()
    {
        return view('install');
    }
}
