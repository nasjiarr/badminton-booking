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
        Schema::create('court_closures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('court_id')->nullable()->constrained('courts')->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['tournament', 'maintenance', 'holiday', 'special_event'])->default('tournament');
            $table->date('start_date');
            $table->date('end_date');
            $table->time('start_time')->default('06:00:00');
            $table->time('end_time')->default('22:00:00');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['start_date', 'end_date']);
            $table->index(['court_id', 'start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('court_closures');
    }
};
