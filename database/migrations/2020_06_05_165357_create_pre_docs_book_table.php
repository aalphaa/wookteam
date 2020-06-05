<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePreDocsBookTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('docs_book', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('username', 100)->nullable()->default('');
			$table->string('title', 100)->nullable()->default('');
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
		Schema::drop('docs_book');
	}
}
