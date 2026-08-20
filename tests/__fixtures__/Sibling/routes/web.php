<?php

use Illuminate\Support\Facades\Route;

Route::get('sibling-web', fn () => 'ok')->name('sibling.web');

Route::livewire('sibling-page', 'sibling::greeting')->name('sibling.page');
