<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Report;
use App\Models\Task;
use App\Models\Finance;
use App\Models\Event;
use App\Models\Lab;
use App\Models\InventoryItem;
use App\Models\AppNotification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Disable foreign keys to cleanly truncate
        Schema::disableForeignKeyConstraints();
        
        // Truncate all tables except users
        Report::truncate();
        Task::truncate();
        Finance::truncate();
        Event::truncate();
        Lab::truncate();
        InventoryItem::truncate();
        DB::table('lab_user')->truncate();
        AppNotification::truncate();

        Schema::enableForeignKeyConstraints();

        // Ensure users exist
        $admin = User::firstOrCreate(['email' => 'admin@example.com'], [
            'name' => 'admin', 'password' => Hash::make('password'), 'role' => 'admin', 'division' => 'Semua Divisi'
        ]);

        $divisions = ['Hubungan Masyarakat', 'IT Support', 'Pemrograman', 'Training', 'Bidang Usaha', 'Badan Pengurus Harian'];
        $usersByDiv = [];

        $specificUsers = [
            ['name' => 'budi', 'email' => 'budi@example.com', 'role' => 'user', 'division' => 'IT Support'],
            ['name' => 'alvy', 'email' => 'alvy@example.com', 'role' => 'user', 'division' => 'IT Support'],
            ['name' => 'nando', 'email' => 'nando@example.com', 'role' => 'user', 'division' => 'IT Support'],
            ['name' => 'annifa', 'email' => 'annifa@example.com', 'role' => 'user', 'division' => 'Bidang Usaha'],
            ['name' => 'firly', 'email' => 'firly@example.com', 'role' => 'user', 'division' => 'Pemrograman'],
            ['name' => 'luluk', 'email' => 'luluk@example.com', 'role' => 'user', 'division' => 'Pemrograman'],
            ['name' => 'saddam', 'email' => 'saddam@example.com', 'role' => 'user', 'division' => 'Hubungan Masyarakat'],
            ['name' => 'ditta', 'email' => 'ditta@example.com', 'role' => 'user', 'division' => 'Hubungan Masyarakat'],
            ['name' => 'steven', 'email' => 'steven@example.com', 'role' => 'user', 'division' => 'Training'],
            ['name' => 'azizah', 'email' => 'azizah@example.com', 'role' => 'user', 'division' => 'Training'],
            ['name' => 'bph1', 'email' => 'bph1@example.com', 'role' => 'admin', 'division' => 'Badan Pengurus Harian'],
            ['name' => 'bph2', 'email' => 'bph2@example.com', 'role' => 'admin', 'division' => 'Badan Pengurus Harian'],
            ['name' => 'bph3', 'email' => 'bph3@example.com', 'role' => 'admin', 'division' => 'Badan Pengurus Harian'],
        ];

        foreach ($specificUsers as $u) {
            $userModel = User::firstOrCreate(['email' => $u['email']], [
                'name' => $u['name'], 'password' => Hash::make('password'), 'role' => $u['role'], 'division' => $u['division']
            ]);
            $usersByDiv[$u['division']][] = $userModel;
        }

        $now = Carbon::now();

        // 1. REPORTS
        foreach ($divisions as $division) {
            $creator = $usersByDiv[$division][0];
            // Pending Report
            Report::create([
                'title' => "Pengajuan Dana Operasional $division",
                'division' => $division,
                'date' => $now->copy()->subDays(2)->toDateString(),
                'budget' => rand(1, 5) * 1000000,
                'description' => "Pengajuan dana untuk kegiatan bulan ini oleh divisi $division.",
                'status' => 'pending',
                'submitted_by' => $creator->id,
            ]);
            // Approved Report
            Report::create([
                'title' => "Laporan Evaluasi $division",
                'division' => $division,
                'date' => $now->copy()->subDays(10)->toDateString(),
                'budget' => rand(2, 8) * 1000000,
                'description' => "Evaluasi program kerja divisi $division pada kuartal sebelumnya.",
                'status' => 'approved',
                'submitted_by' => $creator->id,
                'approved_by' => $admin->id,
                'approved_at' => $now->copy()->subDays(9),
            ]);
            // Rejected Report
            Report::create([
                'title' => "Request Ekstra Budget $division",
                'division' => $division,
                'date' => $now->copy()->subDays(5)->toDateString(),
                'budget' => 15000000,
                'description' => "Permintaan dana dadakan untuk kebutuhan tidak terduga.",
                'status' => 'rejected',
                'submitted_by' => $creator->id,
                'approved_by' => $admin->id,
                'rejection_note' => "Anggaran bulan ini sudah habis. Harap ajukan bulan depan.",
                'approved_at' => $now->copy()->subDays(4),
            ]);
        }

        // 2. TASKS
        $taskTitles = ['Perbaiki Jaringan Lab', 'Desain Poster Lomba', 'Setup Server Ujian', 'Pelatihan Anggota Baru', 'Audit Keuangan', 'Rapat Koordinasi BPH'];
        foreach ($divisions as $i => $division) {
            $assignee = $usersByDiv[$division][0];
            Task::create([
                'title' => $taskTitles[$i],
                'description' => "Tugas ini penting dan harus diselesaikan segera sebelum akhir minggu.",
                'assigned_to' => $assignee->id,
                'assigned_by' => $admin->id,
                'division' => $division,
                'priority' => ['high', 'medium', 'low'][rand(0, 2)],
                'status' => 'pending', // actually 'todo' in frontend/backend maybe mapped differently. Using pending or todo.
                'created_at' => $now->copy()->subDays(rand(1,5)),
            ]);
            Task::create([
                'title' => "Rutin Mingguan $division",
                'description' => "Laporan rutin kegiatan kebersihan dan pemeliharaan.",
                'assigned_to' => $assignee->id,
                'assigned_by' => $admin->id,
                'division' => $division,
                'priority' => 'medium',
                'status' => 'inProgress',
                'created_at' => $now->copy()->subDays(2),
            ]);
        }

        // 3. FINANCES
        $financesData = [
            ['income', 15000000, 'Dana Kampus', 'Pencairan dana kegiatan mahasiswa dari universitas'],
            ['income', 8000000, 'Sponsorship', 'Pemasukan dari sponsor acara tahunan'],
            ['income', 2500000, 'Kas Anggota', 'Iuran kas bulanan pengurus'],
            ['expense', 5000000, 'Operasional IT', 'Biaya langganan server dan domain tahunan'],
            ['expense', 3000000, 'Konsumsi', 'Konsumsi untuk rapat besar dan pelatihan'],
            ['expense', 1500000, 'ATK', 'Pembelian alat tulis kantor dan tinta printer'],
        ];

        foreach ($financesData as $finance) {
            Finance::create([
                'type' => $finance[0],
                'amount' => $finance[1],
                'date' => $now->copy()->subDays(rand(1, 30))->toDateString(),
                'category' => $finance[2],
                'description' => $finance[3],
            ]);
        }

        // 4. EVENTS (Kegiatan)
        $eventsData = [
            ['Rapat Pleno Semester', 'Pemrograman', 'Ruang Sidang 1', $now->copy()->addDays(2)],
            ['Workshop UI/UX Design', 'Hubungan Masyarakat', 'Lab Komputer A', $now->copy()->addDays(5)],
            ['Maintenance Server Tahunan', 'IT Support', 'Ruang Server', $now->copy()->addDays(10)],
            ['Training Asisten Baru', 'Training', 'Lab Komputer B', $now->copy()->addDays(15)],
            ['Bazar Teknologi', 'Bidang Usaha', 'Halaman Kampus', $now->copy()->addDays(20)],
            ['Rapat Kerja BPH', 'Badan Pengurus Harian', 'Ruang Rapat Utama', $now->copy()->addDays(7)],
        ];

        foreach ($eventsData as $event) {
            Event::create([
                'title' => $event[0],
                'division' => $event[1],
                'location' => $event[2],
                'event_date' => $event[3]->toDateString(),
                'event_time' => "09:00",
                'description' => "Kegiatan rutin yang diadakan oleh divisi " . $event[1] . " di " . $event[2],
                'created_by' => $admin->id,
            ]);
        }

        // 5. LABS & INVENTORY
        $labs = [
            ['name' => 'Lab Komputer A', 'description' => 'Lab utama untuk praktikum pemrograman dasar.'],
            ['name' => 'Lab Komputer B', 'description' => 'Lab untuk desain grafis dan multimedia.'],
            ['name' => 'Lab Jaringan', 'description' => 'Lab khusus praktikum jaringan komputer.'],
        ];

        foreach ($labs as $idx => $labData) {
            $lab = Lab::create($labData);
            
            // Assign PICs (IT Support & Pemrograman)
            $lab->pics()->attach([
                $usersByDiv['IT Support'][0]->id,
                $usersByDiv['Pemrograman'][0]->id,
            ]);

            // Add Inventory Items
            InventoryItem::create([
                'lab_id' => $lab->id, 'name' => 'PC Desktop Core i5',
                'condition' => 'Baik', 'quantity' => rand(15, 20), 'notes' => 'Unit lengkap dengan monitor'
            ]);
            InventoryItem::create([
                'lab_id' => $lab->id, 'name' => 'Router Mikrotik',
                'condition' => 'Baik', 'quantity' => rand(2, 5), 'notes' => 'Tersimpan di lemari rack'
            ]);
            InventoryItem::create([
                'lab_id' => $lab->id, 'name' => 'Switch 24 Port',
                'condition' => 'Rusak Ringan', 'quantity' => 1, 'notes' => 'Port 5 dan 6 mati'
            ]);
        }

        // 6. NOTIFICATIONS
        // Send a welcome notification to everyone
        foreach (User::all() as $user) {
            AppNotification::create([
                'user_id' => $user->id,
                'type' => 'system',
                'title' => '🎉 Selamat Datang di ASFOR',
                'body' => 'Sistem Informasi Rekap Laporan Divisi siap digunakan!',
            ]);
        }
    }
}
