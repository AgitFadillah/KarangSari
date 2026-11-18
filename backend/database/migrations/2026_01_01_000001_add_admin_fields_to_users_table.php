<?php

use App\Enums\RoleAdmin;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->unique()->after('name');
            $table->string('role', 30)->default(RoleAdmin::OperatorKonten->value)->after('password');
            $table->boolean('is_active')->default(true)->after('role');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            // Model User memakai SoftDeletes; kolom ini wajib ada.
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Index unique harus di-drop lebih dulu, kalau tidak rollback gagal di SQLite/MySQL.
            $table->dropUnique(['username']);
            $table->dropSoftDeletes();
            $table->dropColumn(['username', 'role', 'is_active', 'last_login_at']);
        });
    }
};
