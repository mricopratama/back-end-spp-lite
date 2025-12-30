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
        // Students table indexes
        Schema::table('students', function (Blueprint $table) {
            $table->index('status', 'idx_students_status');
            $table->index('full_name', 'idx_students_full_name');
            $table->index(['status', 'full_name'], 'idx_students_status_name');
        });

        // Invoices table indexes
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->index('status', 'idx_invoice_items_status');
            $table->index(['student_id', 'status'], 'idx_invoice_items_student_status');
            $table->index(['academic_year_id', 'status'], 'idx_invoice_items_year_status');
        });

        // Payments table indexes
        Schema::table('payments', function (Blueprint $table) {
            $table->index('payment_date', 'idx_payments_date');
            $table->index('payment_method', 'idx_payments_method');
            $table->index(['payment_date', 'payment_method'], 'idx_payments_date_method');
            $table->index('receipt_number', 'idx_payments_receipt');
        });

        // Student class history indexes
        Schema::table('student_class_history', function (Blueprint $table) {
            $table->index(['student_id', 'academic_year_id'], 'idx_sch_student_year');
            $table->index(['class_id', 'academic_year_id'], 'idx_sch_class_year');
        });

        // Notifications table indexes
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['user_id', 'is_read'], 'idx_notifications_user_read');
            $table->index('type', 'idx_notifications_type');
            $table->index('created_at', 'idx_notifications_created');
        });

        // Academic years index
        Schema::table('academic_years', function (Blueprint $table) {
            $table->index('is_active', 'idx_academic_years_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop students indexes
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex('idx_students_status');
            $table->dropIndex('idx_students_full_name');
            $table->dropIndex('idx_students_status_name');
        });

        // Drop invoices indexes
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropIndex('idx_invoice_items_status');
            $table->dropIndex('idx_invoice_items_student_status');
            $table->dropIndex('idx_invoice_items_year_status');
        });

        // Drop payments indexes
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('idx_payments_date');
            $table->dropIndex('idx_payments_method');
            $table->dropIndex('idx_payments_date_method');
            $table->dropIndex('idx_payments_receipt');
        });

        // Drop student class history indexes
        Schema::table('student_class_history', function (Blueprint $table) {
            $table->dropIndex('idx_sch_student_year');
            $table->dropIndex('idx_sch_class_year');
        });

        // Drop notifications indexes
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('idx_notifications_user_read');
            $table->dropIndex('idx_notifications_type');
            $table->dropIndex('idx_notifications_created');
        });

        // Drop academic years index
        Schema::table('academic_years', function (Blueprint $table) {
            $table->dropIndex('idx_academic_years_active');
        });
    }
};
