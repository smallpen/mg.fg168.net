<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',

    // Custom authentication messages
    'login' => [
        'title' => 'Login',
        'username' => 'Username',
        'password' => 'Password',
        'remember' => 'Remember me',
        'submit' => 'Login',
        'failed' => 'Invalid username or password',
        'success' => 'Login successful',
        'logout' => 'Logout',
        'logout_success' => 'Successfully logged out',
    ],

    'validation' => [
        'username_required' => 'Please enter your username',
        'password_required' => 'Please enter your password',
        'username_min' => 'Username must be at least :min characters',
        'password_min' => 'Password must be at least :min characters',
    ],

];