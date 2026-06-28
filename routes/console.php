<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('reservations:clear-expired')->everyMinute();