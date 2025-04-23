<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSummaryTable extends Migration
{
    private const table_name = 'summary';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable(static::table_name)) {
            echo 'Table <'.static::table_name.'> already exists.'.PHP_EOL;
        } else {
            Schema::create(static::table_name, function (Blueprint $table) {
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';
                $table->unsignedInteger('ID')->autoIncrement()->primary();
                $table->string('Title', 255);
                $table->unsignedInteger('GroupID')->comment('各國相同遊戲爲同一個GroupID');
                $table->smallInteger('OrderID')->comment('相同遊戲不同名字間的排序');
                $table->unsignedInteger('GameID')->comment('資料來源表的ID, Ex: GameUs.ID');
                $table->enum('Country', ['us','hk','mx'])->comment('資料來源表, Ex: us -> GameUs');
                $table->string('Boxart', 1023)->comment('縮圖');
                $table->decimal('Price', 10, 3)->comment('該國家最低價格(台幣)')->nullable();
                $table->decimal('MSRP', 10, 3)->comment('建議售價(台幣)')->nullable();
                $table->decimal('LowestPrice', 10, 3)->comment('各國遊戲史低價格(台幣)');
                $table->string('GroupCountry', 100)->comment('Group最低價格的國家')->nullable();
                $table->decimal('GroupPrice', 10, 3)->comment('Group最低價格(台幣)')->nullable();
                $table->decimal('GroupMSRP', 10, 3)->comment('Group最低價的建議售價(台幣)')->nullable();
                $table->double('GroupDiscount')->comment('Group最低價的折扣 X% off');
                $table->tinyInteger('IsLowestPrice')->comment('是否爲史低價格');
                $table->tinyInteger('IsFullChinese')->comment('是否全區支援中文');
                $table->tinyInteger('IsGroupPrice')->comment('是否更新過Group內的價格');
                $table->tinyInteger('IsManual')->comment('人工控制');
                $table->timestamp('CreateTime')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->timestamp('UpdateTime')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->unique('GameID', 'Country');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(static::table_name);
    }
}
