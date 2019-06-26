<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNicknameToSocialAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('social_accounts', 'nickname')) {
                $table->string('nickname', 95)->nullable(); // index contains 2 string less then utf8mb4 max index length 191
            }
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
            if (Schema::hasColumn('social_accounts', 'nickname')) {
                $table->dropColumn('nickname');
            }
        });
    }
}
