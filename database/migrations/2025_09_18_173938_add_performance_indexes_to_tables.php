<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::table('product_data', function (Blueprint $table) {
            $table->index(['customer_id', 'effective_date', 'status'], 'product_data_customer_date_status_idx');
            
            $table->index(['product_id', 'amount', 'status'], 'product_data_amount_status_idx');
            
            $table->index(['effective_date', 'status'], 'product_data_date_status_idx');
            
            $table->index(['customer_id', 'amount'], 'product_data_customer_amount_idx');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->index(['branch_code', 'profitability', 'risk_level'], 'customers_branch_profit_risk_idx');
            
            $table->index(['is_active', 'branch_code'], 'customers_active_branch_idx');
            
            $table->index(['risk_level', 'profitability'], 'customers_risk_profit_idx');
        });

        Schema::table('dashboards', function (Blueprint $table) {
            $table->index(['user_id', 'updated_at'], 'dashboards_user_updated_idx');
        });

        Schema::table('widgets', function (Blueprint $table) {
            $table->index(['dashboard_id', 'type'], 'widgets_dashboard_type_idx');
        });

        Schema::table('formulas', function (Blueprint $table) {
            $table->index(['product_id', 'created_at'], 'formulas_product_created_idx');
        });

        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->index(['model', 'model_id', 'created_at'], 'audit_logs_model_created_idx');
                $table->index(['action', 'created_at'], 'audit_logs_action_created_idx');
            });
        }
    }

    
    public function down(): void
    {
        Schema::table('product_data', function (Blueprint $table) {
            $table->dropIndex('product_data_customer_date_status_idx');
            $table->dropIndex('product_data_amount_status_idx');
            $table->dropIndex('product_data_date_status_idx');
            $table->dropIndex('product_data_customer_amount_idx');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('customers_branch_profit_risk_idx');
            $table->dropIndex('customers_active_branch_idx');
            $table->dropIndex('customers_risk_profit_idx');
        });

        Schema::table('dashboards', function (Blueprint $table) {
            $table->dropIndex('dashboards_user_updated_idx');
        });

        Schema::table('widgets', function (Blueprint $table) {
            $table->dropIndex('widgets_dashboard_type_idx');
        });

        Schema::table('formulas', function (Blueprint $table) {
            $table->dropIndex('formulas_product_created_idx');
        });

        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropIndex('audit_logs_model_created_idx');
                $table->dropIndex('audit_logs_action_created_idx');
            });
        }
    }
};



