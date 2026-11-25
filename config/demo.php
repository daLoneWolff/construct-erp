<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Demo Reset
    |--------------------------------------------------------------------------
    |
    | Gates the "Reset Demo" button and route. The local demo opts in through
    | .env.example (DEMO_RESET_ENABLED=true); other environments are disabled
    | unless they explicitly opt in. The controller also requires the current
    | company to be the fictional demo company.
    |
    */

    'reset_enabled' => env('DEMO_RESET_ENABLED', false),

];
