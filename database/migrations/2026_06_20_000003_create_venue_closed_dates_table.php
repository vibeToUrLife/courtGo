<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_closed_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained('venues')->cascadeOnDelete();
            $table->date('date');
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->unique(['venue_id', 'date']); // a venue can only be closed once per date
        });

        // Closed dates used to be per-court (blocked_dates). Lift any existing ones
        // up to the venue level so nothing that was closed silently reopens. A date
        // blocked on any court of a venue becomes a venue-wide closed date.
        if (Schema::hasTable('blocked_dates')) {
            $rows = DB::table('blocked_dates')
                ->join('courts', 'courts.id', '=', 'blocked_dates.court_id')
                ->select('courts.venue_id', 'blocked_dates.date', 'blocked_dates.reason')
                ->get();

            foreach ($rows as $row) {
                DB::table('venue_closed_dates')->insertOrIgnore([
                    'venue_id' => $row->venue_id,
                    'date' => $row->date,
                    'reason' => $row->reason,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_closed_dates');
    }
};
