<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePreDocsSectionTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('docs_section', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('bookid')->nullable()->default(0)->comment('知识库数据ID');
			$table->integer('parentid')->nullable()->default(0)->comment('上级数据ID');
			$table->string('username', 100)->nullable()->default('');
			$table->string('title', 100)->nullable()->default('');
			$table->string('type', 100)->nullable()->default('');
			$table->integer('inorder')->nullable()->default(0)->comment('排序(DESC)');
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
		Schema::drop('docs_section');
	}
}
