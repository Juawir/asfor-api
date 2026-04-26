<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Report;
use App\Models\Task;
use App\Models\Finance;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admins
        $admin = User::firstOrCreate(['email' => 'admin@example.com'], [
            'name' => 'Super Admin',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $divisions = ['Hubungan Masyarakat', 'IT Support', 'Pemrograman', 'Training', 'Bidang Usaha'];
        $users = [];

        // Users
        foreach ($divisions as $division) {
            $users[$division] = User::firstOrCreate(['email' => strtolower(str_replace(' ', '', $division)) . '@example.com'], [
                'name' => "User $division",
                'password' => Hash::make('password'),
                'role' => 'user',
                'division' => $division,
            ]);
        }

        // Reports
        foreach ($divisions as $division) {
            Report::firstOrCreate([
                'title' => "Laporan Bulanan $division",
                'division' => $division,
                'date' => Carbon::now()->startOfMonth()->toDateString(),
                'budget' => 5000000,
                'description' => "Laporan rutin divisi $division",
                'status' => 'approved'
            ]);
        }

        // Tasks
        foreach ($divisions as $division) {
            Task::firstOrCreate([
                'title' => "Task $division - Main Request",
                'description' => "Tugas dari admin untuk $division",
                'assigned_to' => $users[$division]->id,
                'assigned_by' => $admin->id,
                'division' => $division,
                'priority' => 'high',
                'status' => 'pending'
            ]);
        }

        // Finances - Only handled by Admin or Bidang Usaha logically
        $financesData = [
            ['income', 50000000, 'Dana Hibah', 'Pemasukan dari donasi dan hibah'],
            ['income', 20000000, 'Sponsorship', 'Pemasukan event'],
            ['expense', 5000000, 'Operasional IT', 'Server dan domain'],
            ['expense', 15000000, 'Training', 'Pelatihan SDM bulanan']
        ];

        foreach ($financesData as $finance) {
            Finance::firstOrCreate([
                'type' => $finance[0],
                'amount' => $finance[1],
                'date' => Carbon::now()->toDateString(),
                'category' => $finance[2],
                'description' => $finance[3],
                'percentage' => null
            ]);
        }
    }
}
