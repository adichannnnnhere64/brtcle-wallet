<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->decimal('balance', 15, 2)->default(0); // Use direct value for migration
            $table->morphs('owner');
            $table->timestamps();

            $table->unique(['owner_id', 'owner_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
