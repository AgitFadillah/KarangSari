<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sorotan (highlight) berita.
 *
 * Berita yang ditandai sorotan tampil paling atas dan berukuran besar pada
 * halaman Informasi. Hanya satu berita yang sebaiknya disorot; admin
 * Controller memastikan penandaan baru melepas sorotan lama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('beritas', function (Blueprint $table) {
            $table->boolean('is_highlight')->default(false)->after('kategori')->index();
        });
    }

    public function down(): void
    {
        Schema::table('beritas', function (Blueprint $table) {
            $table->dropIndex(['is_highlight']);
            $table->dropColumn('is_highlight');
        });
    }
};
