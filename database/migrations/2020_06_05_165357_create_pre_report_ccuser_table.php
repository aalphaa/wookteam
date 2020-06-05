<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePreReportCcuserTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('report_ccuser', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('rid')->nullable()->default(0)->comment('汇报ID');
			$table->string('username', 100)->nullable()->default('');
			$table->boolean('cc')->nullable()->default(0)->comment('是否生效');
			$table->unique(['rid','username'], 'IDEX_rid_username');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('report_ccuser');
	}
}
