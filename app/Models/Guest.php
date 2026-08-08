<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Guest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'notes',
        'total_stays',
        'last_stay_at',
    ];

    protected function casts(): array
    {
        return [
            'last_stay_at' => 'date',
        ];
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }

    /**
     * Find a guest by email, or create one. Emails are normalized (trimmed +
     * lowercased) before both the lookup and the insert, and the lookup is
     * case-insensitive so profiles stored under an older, non-normalized
     * casing are still matched instead of duplicated.
     */
    public static function findByEmailOrCreate(string $email, array $attributes = []): self
    {
        $email = self::normalizeEmail($email);

        $guest = static::withTrashed()
            ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
            ->first();

        return $guest ?? static::create([...$attributes, 'email' => $email]);
    }

    public static function normalizeEmail(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }

        $email = trim($email);

        return function_exists('mb_strtolower')
            ? mb_strtolower($email)
            : strtolower($email);
    }
}
