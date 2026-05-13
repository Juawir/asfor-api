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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('division', ['Hubungan Masyarakat', 'IT Support', 'Pemrograman', 'Training', 'Bidang Usaha', 'Badan Pengurus Harian', 'Semua Divisi']);
            $table->date('date');
            $table->decimal('budget', 15, 2)->default(0);
            $table->text('description')->nullable();
            $table->string('attachment')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
