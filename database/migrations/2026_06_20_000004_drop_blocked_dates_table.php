<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-court closed dates have been replaced by per-venue closed dates
     * (venue_closed_dates). The previous migration already lifted any existing
     * rows up to the venue level, so this table is no longer used.
     */
    public function up(): void
    {
        Schema::dropIfExists('blocked_dates');
    }

    public function down(): void
    {
        Schema::create('blocked_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('court_id')->constrained('courts')->cascadeOnDelete();
            $table->date('date');
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->unique(['court_id', 'date']);
        });
    }
};
