<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crop_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crop_chat_session_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['user', 'ai']);
            $table->text('message');
            $table->mediumText('image_base64')->nullable();
            $table->string('image_media_type', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crop_chat_messages');
    }
};
