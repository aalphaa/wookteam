<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePreDocsContentTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('docs_content', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('bookid')->nullable()->default(0)->comment('知识库ID');
			$table->integer('sid')->nullable()->default(0)->index('IDEX_sid')->comment('章节ID');
			$table->text('content')->nullable()->comment('内容');
			$table->string('username', 100)->nullable()->default('');
			$table->bigInteger('indate')->nullable()->default(0);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('docs_content');
	}
}
