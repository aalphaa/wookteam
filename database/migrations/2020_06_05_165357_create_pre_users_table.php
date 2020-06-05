<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePreUsersTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('users', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('identity')->nullable()->default('')->comment('身份');
			$table->string('token', 100)->nullable()->default('')->index('IDEX_token');
			$table->string('username', 100)->nullable()->default('')->unique('IDEX_username')->comment('用户名');
			$table->string('nickname')->nullable()->comment('昵称');
			$table->string('userimg')->nullable()->default('')->comment('已审核头像');
			$table->string('profession')->nullable()->default('')->comment('职称/职位');
			$table->string('encrypt', 50)->nullable()->default('');
			$table->string('userpass', 50)->nullable()->default('')->comment('登录密码');
			$table->integer('wsid')->nullable()->default(0)->index('IDEX_wsid')->comment('websocket');
			$table->bigInteger('wsdate')->nullable()->default(0)->index('IDEX_wsdate')->comment('websocket最后刷新时间');
			$table->integer('bgid')->nullable()->default(0)->comment('背景ID');
			$table->integer('loginnum')->nullable()->default(0)->comment('累计登陆次数');
			$table->string('lastip', 20)->nullable()->default('')->comment('最后登录IP');
			$table->bigInteger('lastdate')->nullable()->default(0)->comment('最后登录时间');
			$table->string('lineip', 20)->nullable()->default('')->index('IDEX_lineip')->comment('最后在线IP（接口）');
			$table->bigInteger('linedate')->nullable()->default(0)->comment('最后在线时间（接口）');
			$table->string('regip', 20)->nullable()->default('')->comment('注册IP');
			$table->bigInteger('regdate')->nullable()->default(0)->comment('注册时间');
			$table->text('setting')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('users');
	}
}
