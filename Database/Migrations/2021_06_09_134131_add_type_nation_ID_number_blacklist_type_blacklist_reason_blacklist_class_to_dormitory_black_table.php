<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTypeNationIDNumberBlacklistTypeBlacklistReasonBlacklistClassToDormitoryBlackTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dormitory_black', function (Blueprint $table) {

            $table->unsignedTinyInteger('type')->default(1)->comment('类型1学生2教职工3维修工4访客5社会人员');
            $table->string('nation', 255)->nullable()->comment('名族');
            $table->string('ID_number', 36)->nullable()->comment('身份证号码');
            $table->integer('blacklist_type')->nullable()->comment('黑名单类型');
            $table->integer('blacklist_reason')->nullable()->comment('黑名单原因');
            $table->integer('blacklist_class')->nullable()->comment('黑名单等级');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dormitory_black', function (Blueprint $table) {

        });
    }
}
