<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->timestamp('scheduled_at')->nullable()->after('closed_at');
            $table->timestamp('scheduled_end')->nullable()->after('scheduled_at');
            $table->boolean('scheduled')->default(false)->after('scheduled_end');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['scheduled_at', 'scheduled_end', 'scheduled']);
        });
    }
};
