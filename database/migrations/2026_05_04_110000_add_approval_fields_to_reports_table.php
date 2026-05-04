<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            if (!Schema::hasColumn('reports', 'submitted_by')) {
                $table->string('submitted_by')->nullable()->after('status');
            }
            if (!Schema::hasColumn('reports', 'approved_by')) {
                $table->string('approved_by')->nullable()->after('submitted_by');
            }
            if (!Schema::hasColumn('reports', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
            if (!Schema::hasColumn('reports', 'rejection_note')) {
                $table->text('rejection_note')->nullable()->after('approved_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn(['submitted_by', 'approved_by', 'approved_at', 'rejection_note']);
        });
    }
};
