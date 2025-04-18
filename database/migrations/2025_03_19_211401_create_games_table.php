<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGamesTable extends Migration
{
    private const table_name = 'game';
    private const countrys = ['hk', 'us', 'mx'];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach (self::countrys as $country) {
            $table_name = self::table_name.'_'.$country;
            if (Schema::hasTable($table_name)) {
                echo "Table <$table_name> already exists.".PHP_EOL;
            } else {
                $this->create_table($table_name);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        foreach (self::countrys as $country) {
            Schema::dropIfExists(self::table_name.'_'.$country);
        }
    }


    private function create_table($table_name)
    {
        Schema::create($table_name, function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->bigIncrements('ID')->primary();
            $table->string('Title', 255)->default('')->unique('email');
            $table->string('URL', 1023)->default('');
            $table->string('NSUID', 100)->default('');
            $table->string('Boxart', 1023)->default('')->comment('縮圖');
            $table->timestamp('ReleaseDate')->nullable();
            $table->string('NumOfPlayers', 20)->default(-1);
            $table->string('Genres', 255)->default('')->comment('類別');
            $table->string('Publishers', 255)->default('')->comment('發行商');
            $table->enum('NSO', ['Yes','No','Unknown'])->default('Unknown')
                      ->comment('Nintendo Switch Online compatible');
            $table->decimal('MSRP', 10, 3)->default(0.000)->comment('建議售價');
            $table->decimal('LowestPrice', 10, 3)->default(0.000)->comment('史低價格');
            $table->string('GameSize', 255)->default('');
            $table->text('Description');
            $table->tinyInteger('TVMode')->default(-1)->comment('TV模式');
            $table->tinyInteger('TabletopMode')->default(-1)->comment('桌上模式');
            $table->tinyInteger('HandheldMode')->default(-1)->comment('掌機模式');
            $table->tinyInteger('SupportEnglish')->default(-1)->comment('支援英文');
            $table->tinyInteger('SupportChinese')->default(-1)->comment('支援中文');
            $table->tinyInteger('SupportJapanese')->default(-1)->comment('支援日文');
            $table->string('GalleryVideo', 1023)->nullable();
            $table->text('GalleryImage')->nullable()->comment('分割符號 = 兩個分號');
            $table->timestamp('CreateTime')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('UpdateTime')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('UpdateInfoTime')->nullable();
            $table->tinyInteger('Sync')->default(0);
        });
    }
}
