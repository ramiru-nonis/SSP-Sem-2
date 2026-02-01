<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Product Variants
        if (!Schema::hasTable('product_variants')) {
            Schema::create('product_variants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->string('color_name'); // e.g., "Midnight Black"
                $table->string('color_code')->nullable(); // e.g., "#000000"
                $table->string('image_url')->nullable();
                $table->integer('stock_quantity')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. Reviews
        if (!Schema::hasTable('reviews')) {
            Schema::create('reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->integer('rating'); // 1-5
                $table->text('comment')->nullable();
                $table->boolean('is_approved')->default(true);
                $table->timestamps();
            });
        }
        
        // 3. Wishlist
        if (!Schema::hasTable('wishlist')) {
            Schema::create('wishlist', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->timestamps();
                $table->unique(['user_id', 'product_id']);
            });
        }

        // 4. Cart Items
        if (!Schema::hasTable('cart_items')) {
            Schema::create('cart_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
                $table->string('session_id')->nullable()->index();
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->integer('quantity')->default(1);
                $table->timestamps();
            });
        }

        // 5. Coupons
        if (!Schema::hasTable('coupons')) {
            Schema::create('coupons', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('type'); // 'Fixed Amount', 'Percentage'
                $table->decimal('amount', 8, 2);
                $table->decimal('minimum_amount', 8, 2)->nullable();
                $table->integer('usage_limit')->nullable();
                $table->integer('used_count')->default(0);
                $table->timestamp('expires_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 6. Orders
        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->string('order_number')->unique();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('status')->default('Pending');
                $table->string('currency')->default('USD');
                
                $table->decimal('subtotal', 10, 2);
                $table->decimal('tax_amount', 10, 2)->default(0);
                $table->decimal('shipping_amount', 10, 2)->default(0);
                $table->decimal('discount_amount', 10, 2)->default(0);
                $table->decimal('total_amount', 10, 2);
                
                $table->string('payment_method')->nullable();
                $table->string('payment_status')->default('Pending');
                
                // Shipping/Billing Info
                $table->string('billing_first_name')->nullable();
                $table->string('billing_last_name')->nullable();
                $table->string('billing_email')->nullable();
                $table->string('billing_phone')->nullable();
                $table->string('billing_address_1')->nullable();
                $table->string('billing_city')->nullable();
                $table->string('billing_state')->nullable();
                $table->string('billing_postal_code')->nullable();
                $table->string('billing_country')->nullable();
                
                $table->string('shipping_address_1')->nullable();
                $table->string('shipping_city')->nullable();
                $table->string('shipping_state')->nullable();
                
                $table->timestamp('order_date')->useCurrent();
                $table->timestamp('shipped_date')->nullable();
                $table->timestamp('delivered_date')->nullable();
                $table->timestamps();
            });
        }

        // 7. Order Items
        if (!Schema::hasTable('order_items')) {
            Schema::create('order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained()->onDelete('cascade');
                $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');
                $table->string('product_name');
                $table->string('product_sku')->nullable();
                $table->integer('quantity');
                $table->decimal('price', 10, 2);
                $table->decimal('total', 10, 2);
            });
        }

        // 8. Settings
        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('setting_key')->unique();
                $table->text('setting_value')->nullable();
                $table->string('setting_type')->default('text'); // text, boolean, number, json
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }

        // 9. Product Attributes
        if (!Schema::hasTable('product_attributes')) {
            Schema::create('product_attributes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->string('attribute_name');
                $table->string('attribute_value');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_attributes');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('wishlist');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('product_variants');
    }
};
