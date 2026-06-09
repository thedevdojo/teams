<?php

use Devdojo\Teams\Http\Controllers\TeamInvitationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('team-invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])
        ->middleware('signed')
        ->name('teams.invitations.accept');
});
