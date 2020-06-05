<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePreReportContentTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('report_content', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('rid')->nullable()->default(0)->unique('IDEX_rid')->comment('汇报ID');
			$table->text('content')->nullable()->comment('内容');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('report_content');
	}
}
