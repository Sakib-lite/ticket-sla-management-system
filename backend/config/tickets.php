<?php

return [

    'number_prefix' => env('TICKET_NUMBER_PREFIX', 'TKT'),

    'number_minimum_digits' => 6,

    'auto_assign' => (bool) env('TICKET_AUTO_ASSIGN', true),

];
