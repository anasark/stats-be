<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $driver = DB::getDriverName();

        Schema::create('user_preferences', function (Blueprint $table) use ($driver) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            if ($driver === 'pgsql') {
                // PostgreSQL: boleh pakai default JSON
                $table->json('platforms')->default('[]');
            } else {
                // MySQL: TIDAK boleh default JSON
                $table->json('platforms');
            }

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
