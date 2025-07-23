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
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('provider', 30);
            $table->string('provider_user_id', 100);
            $table->string('access_token');
            $table->string('refresh_token')->nullable();
            // index contains 2 string less then utf8mb4 max index length 191
            $table->string('nickname', 95)->nullable();
            $table->string('name', 95)->nullable();
            $table->string('email', 95)->nullable();
            $table->string('avatar', 1024)->nullable();
            $table->text('raw')->nullable();
            $table->unsignedInteger('expires_in')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'provider_user_id']);
            $table->index(['user_id']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
            if (!Schema::hasColumn('users', 'avatar')) {
                $table->string('avatar')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'avatar')) {
                $table->dropColumn('avatar');
            }
        });
    }
};
