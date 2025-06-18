<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('olx_ads', function (Blueprint $table) {
            // Додаємо новий стовпець 'title' типу string.
            // makeNullable() дозволяє йому бути NULL, якщо назва не буде отримана.
            // after('last_checked_at') - додає його після стовпця last_checked_at (необов'язково).
            $table->string('title')->nullable()->after('last_checked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('olx_ads', function (Blueprint $table) {
            // При відкаті міграції, видаляємо стовпець 'title'.
            $table->dropColumn('title');
        });
    }
};
