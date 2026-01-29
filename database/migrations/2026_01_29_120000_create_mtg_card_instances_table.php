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
        Schema::create('mtg_card_instances', function (Blueprint $table) {
            $table->id();
            
            // Foreign keys to establish many-to-many relationship
            $table->string('scryfall_id', 36)->index()->comment('Foreign key to mtg_cards.scryfall_id');
            $table->unsignedBigInteger('location_id')->index()->comment('Foreign key to mtg_locations.id');
            
            // Quantity of this card at this location
            $table->integer('quantity')->default(1)->comment('Number of copies at this location (must be >= 0)');
            
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('scryfall_id')
                ->references('scryfall_id')
                ->on('mtg_cards')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            
            $table->foreign('location_id')
                ->references('id')
                ->on('mtg_locations')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            
            // Composite unique constraint - one record per card per location
            $table->unique(['scryfall_id', 'location_id'], 'card_location_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mtg_card_instances');
    }
};
