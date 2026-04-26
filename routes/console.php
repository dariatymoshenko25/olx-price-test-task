<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('app:dispatch-price-checks-command')
    ->everyTwoMinutes()
    ->withoutOverlapping();
