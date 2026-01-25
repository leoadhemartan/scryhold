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
        Schema::create('mtg_sets', function (Blueprint $table) {
            $table->id();
            $table->string('set_code')->unique();
            $table->string('set_name');
            $table->string('set_svg_url')->nullable();
            $table->string('set_release_date')->nullable();
            $table->string('set_type')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mtg_sets');
    }
};