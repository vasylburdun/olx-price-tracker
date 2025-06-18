<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('olx_ad_id')->constrained('olx_ads')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['user_id', 'olx_ad_id']); // Each user can subscribe to an ad only once
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
