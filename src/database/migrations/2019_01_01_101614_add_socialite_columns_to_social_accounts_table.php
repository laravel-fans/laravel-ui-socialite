<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSocialiteColumnsToSocialAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->string('nickname', 95)->nullable(); // index contains 2 string less then utf8mb4 max index length 191
            $table->string('name', 95)->nullable();
            $table->string('email', 95)->nullable();
            $table->string('avatar', 1024)->nullable();
            $table->text('raw')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->dropColumn('nickname');
            $table->dropColumn('name');
            $table->dropColumn('email');
            $table->dropColumn('avatar');
            $table->dropColumn('raw');
        });
    }
}
