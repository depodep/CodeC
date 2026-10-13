<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutboundMessage extends Model
{
    use HasFactory;

    protected $table = 'outbound_messages';

    protected $fillable = [
        'sender_user_id',
        'student_id',
        'recipient_name',
        'contact_number',
        'message',
        'sender_id',
        'gateway_message_id',
        'status',
        'dispatch_mode',
        'notification_key',
        'api_response',
        'error_message',
        'last_sent_at',
    ];

    protected $casts = [
        'last_sent_at' => 'datetime',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    /**
     * The sender who initiated this outbound SMS.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    /**
     * The student related to this outbound SMS (if any).
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Scope for successful sends (sent or delivered).
     */
    public function scopeSent($query)
    {
        return $query->whereIn('status', ['sent', 'delivered']);
    }

    /**
     * Scope for failed dispatches.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for pending messages.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Helper to resolve display sender name.
     */
    public function getSenderDisplayNameAttribute(): string
    {
        if ($this->sender) {
            return $this->sender->full_name ?: ($this->sender->first_name . ' ' . $this->sender->last_name);
        }

        return $this->sender_id ?: 'SIATRACK SYS';
    }
}
