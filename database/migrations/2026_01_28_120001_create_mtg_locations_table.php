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
        Schema::create('mtg_locations', function (Blueprint $table) {
            $table->id();
            
            // Core location fields
            $table->string('name')->unique()->comment('Unique name for the location');
            $table->string('location_type', 50)->index()->comment('Type of location (e.g., collection, deck, binder)');
            $table->string('deck_type', 50)->nullable()->index()->comment('Format or style of deck');
            $table->boolean('is_default')->default(false)->index()->comment('Whether this is the default location for new cards');
            
            // Conditional fields based on location type
            $table->string('commander')->nullable()->comment('Commander card name (only for commander_deck type)');
            $table->foreignId('side_deck_parent')->nullable()->constrained('mtg_locations')->onDelete('set null')->comment('Parent location for side decks');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mtg_locations');
    }
};
