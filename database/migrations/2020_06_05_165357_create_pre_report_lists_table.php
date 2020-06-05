<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePreReportListsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('report_lists', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('username', 100)->nullable()->default('');
			$table->string('title', 100)->nullable()->default('');
			$table->string('type', 100)->nullable()->default('');
			$table->string('status', 100)->nullable()->default('');
			$table->text('ccuser')->nullable()->comment('抄送人');
			$table->string('date', 20)->nullable()->default('')->comment('日期');
			$table->bigInteger('indate')->nullable()->default(0);
			$table->unique(['username','type','date'], 'IDEX_username_type_date');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('report_lists');
	}
}
