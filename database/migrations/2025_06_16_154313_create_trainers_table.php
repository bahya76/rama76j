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
        Schema::create('trainers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email')->unique();
            $table->string('password');
            $table->string('registration_code',10); // كود التسجيل المقدم من الأدمن الأساسي
            $table->unsignedBigInteger('super_admin_id'); // الربط مع الأدمن الأساسي
            $table->timestamps();

            // المفتاح الخارجي (Foreign key) مع حذف المدربين عند حذف الأدمن الأساسي
            $table->foreign('super_admin_id')->references('id')->on('super_admins')->onDelete('cascade');
            $table->index('registration_code'); // لتسريع البحث

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trainers');
    }
};
