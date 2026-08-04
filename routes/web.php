<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes (Single Page Application Fallback)
|--------------------------------------------------------------------------
|
| All web routes render the Vue 3 SPA shell view (app.blade.php).
| Vue Router inside the SPA handles client-side page routing.
|
*/

Route::get('/{any?}', function () {
    return view('app');
})->where('any', '.*')->name('spa');
