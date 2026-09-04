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
        Schema::table('invoices', function (Blueprint $table) {
            $table->boolean('show_paid_stamp')->default(true);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->boolean('show_paid_stamp')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('show_paid_stamp');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('show_paid_stamp');
        });
    }
};
