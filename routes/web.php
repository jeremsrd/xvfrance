<?php

use App\Http\Controllers\CoachController;
use App\Http\Controllers\CompetitionController;
use App\Http\Controllers\CompetitionEditionController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\OpponentController;
use App\Http\Controllers\PlayerController;
use App\Livewire\MatchList;
use App\Livewire\PlayerList;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/matches', MatchList::class)->name('matches.index');
Route::get('/matches/{rugbyMatch:slug}', [MatchController::class, 'show'])->name('matches.show');
Route::get('/joueurs', PlayerList::class)->name('players.index');
Route::get('/joueurs/{player:slug}', [PlayerController::class, 'show'])->name('players.show');
Route::get('/adversaires', [OpponentController::class, 'index'])->name('opponents.index');
Route::get('/adversaires/{country:code}', [OpponentController::class, 'show'])->name('opponents.show');
Route::get('/competitions', [CompetitionController::class, 'index'])->name('competitions.index');
Route::get('/competitions/{competition}', [CompetitionController::class, 'show'])->name('competitions.show');
Route::get('/competitions/editions/{competitionEdition}', [CompetitionEditionController::class, 'show'])->name('editions.show');
Route::get('/selectionneurs', [CoachController::class, 'index'])->name('coaches.index');
Route::get('/selectionneurs/{coach}', [CoachController::class, 'show'])->name('coaches.show');
