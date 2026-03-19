<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\OpponentController;
use App\Livewire\MatchList;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/matches', MatchList::class)->name('matches.index');
Route::get('/matches/{rugbyMatch}', [MatchController::class, 'show'])->name('matches.show');
Route::get('/adversaires', [OpponentController::class, 'index'])->name('opponents.index');
Route::get('/adversaires/{country:code}', [OpponentController::class, 'show'])->name('opponents.show');
