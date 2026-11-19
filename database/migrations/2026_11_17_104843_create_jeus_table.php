
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jeus', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->foreignId('categorie_id')
                  ->constrained('categories')
                  ->cascadeOnDelete();

            $table->string('nom');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->integer('nombre_joueurs')->nullable();
            $table->integer('nombre_equipes')->nullable();
            $table->boolean('est_payant')->default(false);
            $table->decimal('montant_inscription', 10, 2)->nullable();
            $table->string('mode_competition')->nullable();
            $table->date('date_demarrage')->nullable();
            $table->date('date_limite_inscription')->nullable();
            $table->string('statut')->default('en_attente');

            $table->boolean('active')->default(true);
            $table->boolean('deleted')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jeus');
    }
};
