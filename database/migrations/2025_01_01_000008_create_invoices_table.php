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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->string('title');
            $table->unsignedTinyInteger('period_month'); // 1-12 untuk semua invoice type
            $table->unsignedInteger('period_year'); // 2024, 2025, dst untuk semua invoice type
            $table->enum('invoice_type', ['spp_monthly', 'spp_yearly', 'other_fee', 'other'])->default('other');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->enum('status', ['unpaid', 'partial', 'paid'])->default('unpaid');
            $table->date('due_date');
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->timestamps();

            // Indexes untuk performa query
            $table->index(['student_id', 'period_month', 'period_year'], 'idx_student_period');
            $table->index(['invoice_type', 'period_month', 'period_year'], 'idx_type_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
