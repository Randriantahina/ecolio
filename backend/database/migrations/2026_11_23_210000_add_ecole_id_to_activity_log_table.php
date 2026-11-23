<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nullable: platform-level events (super admin actions) belong to no school.
        Schema::table('activity_log', function (Blueprint $table) {
            $table->foreignId('ecole_id')->nullable()->after('id')->constrained('ecoles')->restrictOnDelete();
            $table->index(['ecole_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ecole_id');
        });
    }
};
