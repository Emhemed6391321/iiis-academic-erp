<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (!Schema::hasColumn('branches', 'facebook_url')) {
                $table->text('facebook_url')->nullable()->after('email');
            }
            if (!Schema::hasColumn('branches', 'telegram_url')) {
                $table->text('telegram_url')->nullable()->after('facebook_url');
            }
            if (!Schema::hasColumn('branches', 'whatsapp_number')) {
                $table->string('whatsapp_number', 50)->nullable()->after('telegram_url');
            }
            if (!Schema::hasColumn('branches', 'website_url')) {
                $table->text('website_url')->nullable()->after('whatsapp_number');
            }
            if (!Schema::hasColumn('branches', 'cover_image')) {
                $table->text('cover_image')->nullable()->after('website_url');
            }
            if (!Schema::hasColumn('branches', 'photos')) {
                $table->json('photos')->nullable()->after('cover_image');
            }
            if (!Schema::hasColumn('branches', 'social_links')) {
                $table->json('social_links')->nullable()->after('photos');
            }
        });

        Schema::table('branch_classes', function (Blueprint $table) {
            if (!Schema::hasColumn('branch_classes', 'room_type')) {
                $table->string('room_type', 50)->default('CLASSROOM')->after('stage');
            }
            if (!Schema::hasColumn('branch_classes', 'floor')) {
                $table->string('floor', 50)->nullable()->after('room_type');
            }
            if (!Schema::hasColumn('branch_classes', 'equipment')) {
                $table->json('equipment')->nullable()->after('floor');
            }
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn([
                'facebook_url',
                'telegram_url',
                'whatsapp_number',
                'website_url',
                'cover_image',
                'photos',
                'social_links',
            ]);
        });

        Schema::table('branch_classes', function (Blueprint $table) {
            $table->dropColumn([
                'room_type',
                'floor',
                'equipment',
            ]);
        });
    }
};
