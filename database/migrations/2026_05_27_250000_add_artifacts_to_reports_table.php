<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->string('csv_path')->nullable()->after('author');
            $table->json('analytics')->nullable()->after('csv_path');
            $table->string('pdf_path')->nullable()->after('analytics');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn(['csv_path', 'analytics', 'pdf_path']);
        });
    }
};
