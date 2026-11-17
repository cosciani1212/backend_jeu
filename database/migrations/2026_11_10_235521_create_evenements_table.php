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
        Schema::create('evenements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('structure_id')->constrained('structures')->cascadeOnDelete();
            $table->string('titre');
            $table->text('description')->nullable();
            $table->string('lieu')->nullable();
            $table->dateTime('date_debut');
            $table->dateTime('date_fin')->nullable();
            $table->string('statut')->default('en_attente');
            $table->timestamps();
            $table->boolean('active')->default(true);
            $table->boolean('deleted')->default(false);
            $table->foreignId('inserted_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evenements');
    }
};
