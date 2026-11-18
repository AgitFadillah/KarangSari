<?php

namespace App\Enums;

use Illuminate\Support\Str;

enum RoleAdmin: string
{
    case Administrator = 'administrator';
    case Verifikator = 'verifikator';
    case OperatorKonten = 'operator_konten';

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Administrator',
            self::Verifikator => 'Verifikator',
            self::OperatorKonten => 'Operator Konten',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Administrator => 'Admin',
            self::Verifikator => 'Verifikator',
            self::OperatorKonten => 'Operator',
        };
    }

    /** Label badge pada tabel akun admin. */
    public function badge(): string
    {
        return match ($this) {
            self::Administrator => 'badge-blue',
            self::Verifikator => 'badge-blue',
            self::OperatorKonten => 'badge-gray',
        };
    }

    /** Dua huruf untuk avatar, mis. "Sari Wulandari" -> "SW". */
    public static function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= Str::upper(Str::substr($part, 0, 1));
        }

        return $initials !== '' ? $initials : 'AD';
    }

    public function bolehVerifikasiPengajuan(): bool
    {
        return in_array($this, [self::Administrator, self::Verifikator], true);
    }

    public function bolehKelolaAkun(): bool
    {
        return $this === self::Administrator;
    }

    public function bolehKelolaJenisSurat(): bool
    {
        return in_array($this, [self::Administrator, self::Verifikator], true);
    }

    public function bolehKelolaKonten(): bool
    {
        return in_array($this, [self::Administrator, self::OperatorKonten], true);
    }
}
