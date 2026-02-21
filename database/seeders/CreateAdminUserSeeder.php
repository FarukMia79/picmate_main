<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class CreateAdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::create([
            'name' => 'Hardik Savani',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('123456'),
            'image' => 'default.png', // এই লাইনটি যোগ করুন
            'status' => 1            // এই লাইনটিও যোগ করুন
        ]);

        // রোল তৈরি বা খুঁজে নেওয়া (যাতে এরর না আসে)
        $role = Role::firstOrCreate(['name' => 'Admin']);

        $permissions = Permission::pluck('id', 'id')->all();
        $role->syncPermissions($permissions);

        $user->assignRole([$role->id]);
    }
}
