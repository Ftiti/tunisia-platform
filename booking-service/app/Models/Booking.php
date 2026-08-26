<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    protected $fillable = [
        'user_id', 'provider_id', 'service_id',
        'scheduled_date', 'scheduled_time', 'end_time',
        'status', 'total_price', 'notes',
        'cancellation_reason', 'cancelled_at',
        'confirmed_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'total_price'    => 'float',
            'cancelled_at'   => 'datetime',
            'confirmed_at'   => 'datetime',
            'completed_at'   => 'datetime',
        ];
    }

    // ── Relations locales ─────────────────────────────────────────────────────
    // provider_id et service_id sont des références externes vers le Provider Service.
    // Les données sont enrichies via ProviderServiceClient, pas via Eloquent.

    public function reminders(): HasMany
    {
        return $this->hasMany(BookingReminder::class);
    }

    // ── Helpers statut ────────────────────────────────────────────────────────
    public function isPending(): bool   { return $this->status === 'pending'; }
    public function isConfirmed(): bool { return $this->status === 'confirmed'; }
    public function isCancelled(): bool { return $this->status === 'cancelled'; }
    public function isCompleted(): bool { return $this->status === 'completed'; }
}
