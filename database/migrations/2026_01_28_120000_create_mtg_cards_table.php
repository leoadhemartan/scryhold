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
        Schema::create('mtg_cards', function (Blueprint $table) {
            $table->id();
            
            // Core identification fields
            $table->string('scryfall_id', 36)->unique()->comment('Scryfall unique identifier (UUID)');
            $table->string('name')->index()->comment('Full card name');
            $table->string('layout', 50)->index()->comment('Card layout type (e.g., normal, split, transform)');
            $table->string('lang', 10)->default('en')->index()->comment('Language code (ISO 639-1)');
            
            // Single-faced card fields (nullable for multi-faced cards)
            $table->string('type_line')->nullable()->comment('Type line for single-faced cards');
            $table->string('mana_cost', 100)->nullable()->comment('Mana cost for single-faced cards');
            $table->text('oracle_text')->nullable()->comment('Oracle rules text for single-faced cards');
            
            // Card face left (first face) fields
            $table->string('cfl_name')->nullable()->comment('Name of the left/first face');
            $table->string('cfl_mana_cost', 100)->nullable()->comment('Mana cost of the left/first face');
            $table->string('cfl_type_line')->nullable()->comment('Type line of the left/first face');
            $table->text('cfl_oracle_text')->nullable()->comment('Oracle text of the left/first face');
            
            // Card face right (second face) fields
            $table->string('cfr_name')->nullable()->comment('Name of the right/second face');
            $table->string('cfr_mana_cost', 100)->nullable()->comment('Mana cost of the right/second face');
            $table->string('cfr_type_line')->nullable()->comment('Type line of the right/second face');
            $table->text('cfr_oracle_text')->nullable()->comment('Oracle text of the right/second face');
            
            // Image paths (relative paths to local storage)
            $table->string('image_uri', 500)->nullable()->comment('Relative path to locally stored image');
            $table->string('cfl_image_uri', 500)->nullable()->comment('Relative path to left face image');
            $table->string('cfr_image_uri', 500)->nullable()->comment('Relative path to right face image');
            
            // Complete API response storage
            $table->json('scryfall_json')->comment('Complete Scryfall API JSON response');
            
            $table->timestamps();
            
            // Additional indexes for common queries
            $table->index(['lang', 'layout']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mtg_cards');
    }
};
