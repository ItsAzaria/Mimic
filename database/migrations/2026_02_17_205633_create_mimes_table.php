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
        Schema::create('mimes', function (Blueprint $table) {
            $table->id();
            $table->string('mime')->unique();
            $table->enum('handling', ['ALLOW', 'UPLOAD'])->default('ALLOW');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mimes');
    }
};
