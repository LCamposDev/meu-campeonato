<?php

return [

    'python_binary' => env('SCORE_GENERATOR_PYTHON', 'python3'),

    'script_path' => env('SCORE_GENERATOR_SCRIPT', base_path('teste.py')),

    'fallback_on_failure' => env('SCORE_GENERATOR_FALLBACK', false),

];
