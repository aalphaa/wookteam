<?php

use Illuminate\Database\Seeder;

class SettingTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('setting')->delete();
        
        \DB::table('setting')->insert(array (
            0 => 
            array (
                'id' => 1,
                'title' => 'system',
                'desc' => '',
                'setting' => '{\\"reg\\":\\"open\\",\\"github\\":\\"show\\",\\"logo\\":\\"\\"}',
            ),
        ));
        
        
    }
}