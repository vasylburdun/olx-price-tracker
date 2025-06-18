<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('olx_ads', function (Blueprint $table) {
            $table->id();
            $table->string('url')->unique();
            $table->decimal('current_price', 10, 2)->nullable();
            $table->string('currency', 10)->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('olx_ads');
    }
};
