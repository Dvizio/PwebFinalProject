<?php

namespace App\Models;

use App\Constants\PaymentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'issued_at',
        'status_message',
        'midtrans_transaction_id',
        'payment_type',
        'transaction_status',
        'bank',
        'va_number',
        'fraud_status',
        'pdf_url',
        'snap_token',
    ];

    protected $casts = ['transaction_status', PaymentStatus::class];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
