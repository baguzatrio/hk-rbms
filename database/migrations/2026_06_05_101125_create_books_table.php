<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->time('start_hour');
            $table->time('finish_hour');
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->text('remarks')->nullable();
     
            // Kolom tambahan untuk approval tracking
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('pic_name')->nullable();         // Person In Charge
            $table->text('participants')->nullable();        // daftar peserta (teks bebas)
     
            $table->timestamps();
     
            // Index untuk query yang sering digunakan
            $table->index(['room_id', 'start_date', 'end_date'], 'idx_books_room_date');
            $table->index('status');
            $table->index('requester_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
