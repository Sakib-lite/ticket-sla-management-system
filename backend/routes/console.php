<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('sla:monitor')->everyMinute()->withoutOverlapping();
