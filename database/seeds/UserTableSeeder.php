<?php

use Illuminate\Database\Seeder;
use Carbon\Carbon;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            'name' => 'test',
            'email' => 'test@test.com',
            'password' => bcrypt('123456'),
            'stu_id' => '2015211115',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
        
        mkdir(storage_path('app' . DIRECTORY_SEPARATOR . 'public/2015211115'));
    }
}
