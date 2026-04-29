<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ZoneController;
use App\Http\Controllers\N8nProxyController;
use App\Http\Controllers\CropChatController;
use App\Http\Controllers\SubsidyController;
use App\Http\Controllers\YieldController;
use App\Http\Controllers\TelegramMessageController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::patch('/zone', [ZoneController::class, 'store'])->name('zone.store');
    Route::get('/zone/fires', [ZoneController::class, 'getFires'])->name('zone.fires');

    Route::post('/n8n/analyze', [N8nProxyController::class, 'analyze'])->name('n8n.analyze');

    Route::get('/crop/sessions',         [CropChatController::class, 'sessions'])->name('crop.sessions');
    Route::get('/crop/sessions/{id}',    [CropChatController::class, 'sessionMessages'])->name('crop.session.messages');
    Route::delete('/crop/sessions/{id}', [CropChatController::class, 'deleteSession'])->name('crop.session.delete');
    Route::post('/n8n/crop',             [CropChatController::class, 'chat'])->name('n8n.crop');

    Route::post('/demo/notify', function (\Illuminate\Http\Request $request) {
        $type   = $request->input('type'); // 'no_fire' | 'fire'
        $oblast = $request->input('oblast', 'регион не указан');

        $text = $type === 'fire'
            ? "🔥 <b>ПОЖАР ОБНАРУЖЕН</b>\nРегион: <b>{$oblast}</b>\nСистема AgriFireShield зафиксировала активные очаги возгорания. Требуется внимание!"
            : "✅ <b>Пожаров нет</b>\nРегион: <b>{$oblast}</b>\nАктивных очагов возгорания не обнаружено. Ситуация в норме.";

        $chatIds = array_filter([
            config('services.telegram.almat'),
            config('services.telegram.beka'),
        ]);

        foreach ($chatIds as $chatId) {
            TelegramMessageController::sendMessage($chatId, $text);
        }

        return response()->json(['ok' => true]);
    })->name('demo.notify');

    Route::get('/subsidies', [SubsidyController::class, 'index'])->name('subsidies');
    Route::get('/yield',     [YieldController::class,  'index'])->name('yield');
});
