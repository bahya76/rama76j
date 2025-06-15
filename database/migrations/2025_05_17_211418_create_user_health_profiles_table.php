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
        Schema::create('user_health_profiles', function (Blueprint $table) {
            $table->id();

            // User relationship
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Required fields
            $table->string('full_name');
            $table->double('height'); // in centimeters
            $table->enum('fitness_level', ['beginner', 'intermediate', 'advanced']);
            $table->enum('goal', ['lose_weight', 'gain_weight', 'build_muscle', 'stay_fit']);

            // Optional fields
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->integer('age')->nullable();
            $table->double('weight')->nullable();
            $table->enum('fat_distribution', ['abdomen', 'thighs', 'arms', 'hips', 'general'])->nullable();

            $table->text('chronic_diseases_or_injuries')->nullable();

            $table->double('waist_circumference')->nullable();
            $table->double('hip_circumference')->nullable();
            $table->double('chest_circumference')->nullable();
            $table->double('arm_circumference')->nullable();

            $table->integer('workout_days_per_week')->nullable();
            $table->enum('preferred_meals_count', ['2', '3', '4', '5'])->nullable();
  // Optional: track last update datetime separately
            $table->timestamp('last_updated_at')->nullable();
            $table->json('changed_data');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_health_profiles');
    }
};
