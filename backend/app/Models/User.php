<?php

namespace App\Models;

use App\Enums\RoleAdmin;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'email', 'password', 'role', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'role' => RoleAdmin::class,
            'is_active' => 'boolean',
        ];
    }

    /** Dua huruf untuk avatar di sidebar/topbar admin. */
    protected function initials(): Attribute
    {
        return Attribute::get(fn (): string => RoleAdmin::initials($this->name));
    }

    public function pengajuanDiproses(): HasMany
    {
        return $this->hasMany(Pengajuan::class, 'diproses_oleh');
    }

    public function logAktivitas(): HasMany
    {
        return $this->hasMany(LogAktivitas::class);
    }

    public function isAdministrator(): bool
    {
        return $this->role === RoleAdmin::Administrator;
    }
}
