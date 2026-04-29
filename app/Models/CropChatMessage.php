<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CropChatMessage extends Model
{
    protected $fillable = ['crop_chat_session_id', 'role', 'message', 'image_base64', 'image_media_type'];

    public function session(): BelongsTo
    {
        return $this->belongsTo(CropChatSession::class, 'crop_chat_session_id');
    }
}
