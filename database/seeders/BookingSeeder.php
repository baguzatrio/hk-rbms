<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Book;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = Room::all();
        $users = User::all();
        $admin = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->first() ?? $users->first();

        if ($rooms->isEmpty() || $users->isEmpty()) {
            $this->command->warn('Rooms or Users table is empty. Please run RoomSeeder and UserSeeder first.');
            return;
        }

        $meetingTitles = [
            'Rapat Koordinasi Mingguan Divisi ERTI',
            'Evaluasi Proyek Infrastruktur Q3',
            'Briefing Pengadaan Material & Logistik',
            'Workshop Transformasi Digital & RBMS',
            'Interview Calon Karyawan Human Capital',
            'Rapat Tinjauan Manajemen (RTM)',
            'Sosialisasi Kebijakan K3 Lingkungan Kerja',
            'Sync Up Tim Pengembang Aplikasi',
            'Diskusi Anggaran & Anggaran Tambahan Divisi',
            'Pelatihan Security Awareness & Cyber Security',
            'Review Progress Proyek Jalan Tol HK',
            'Townhall Meeting & Halal Bihalal Karyawan',
            'Kickoff Meeting Implementation ERP Core',
            'Sesi Brainstorming Inovasi Teknologi',
            'Rapat Anggaran Tahunan & Strategi Bisnis',
            'Vendor Alignment & Partnership Review',
            'Audit Internal ISO 9001 & ISO 27001',
            'Rapat Direksi & Pemegang Saham',
            'Pelatihan Manajemen Risiko Operasional',
            'Konsolidasi Laporan Keuangan Bulanan',
        ];

        $rejectionReasons = [
            'Ruangan akan digunakan untuk rapat direksi mendadak.',
            'Jadwal berbenturan dengan agenda prioritas holding.',
            'Kapasitas ruangan tidak mencukupi jumlah peserta.',
            'Fasilitas proyektor dan audio di ruangan sedang dalam perawatan.',
            'Permohonan dibatalkan atas permintaan pemohon.',
        ];

        $timeSlots = [
            ['08:00:00', '10:00:00'],
            ['08:30:00', '10:30:00'],
            ['09:00:00', '11:30:00'],
            ['10:00:00', '12:00:00'],
            ['10:30:00', '12:30:00'],
            ['13:00:00', '15:00:00'],
            ['13:30:00', '15:30:00'],
            ['14:00:00', '16:00:00'],
            ['14:30:00', '16:30:00'],
            ['08:30:00', '16:00:00'], // Full day workshop
        ];

        $statuses = ['approved', 'approved', 'approved', 'pending', 'rejected', 'cancelled'];

        $targetCount = 100;
        $createdCount = 0;
        $attempts = 0;
        $maxAttempts = 500;

        // Generate start dates from July 1, 2026 to August 31, 2026
        $startDateRange = Carbon::create(2026, 7, 1);
        $endDateRange = Carbon::create(2026, 8, 31);

        while ($createdCount < $targetCount && $attempts < $maxAttempts) {
            $attempts++;

            // Pick a random day between July 1 and Aug 31
            $randomDays = rand(0, $startDateRange->diffInDays($endDateRange));
            $meetingDate = $startDateRange->copy()->addDays($randomDays);

            // Skip weekends for realistic business bookings (unless max attempts high)
            if ($meetingDate->isWeekend() && rand(1, 10) > 2) {
                continue;
            }

            $room = $rooms->random();
            $requester = $users->random();

            // Multi-day duration (10% chance)
            $isMultiDay = (rand(1, 100) <= 10);
            $endDate = $isMultiDay ? $meetingDate->copy()->addDays(rand(1, 2)) : $meetingDate->copy();

            $slot = $timeSlots[array_rand($timeSlots)];
            $startHour = $slot[0];
            $finishHour = $slot[1];

            // Determine status based on date & random distribution
            $today = Carbon::create(2026, 8, 3);
            if ($meetingDate->lt($today->copy()->subDays(2))) {
                // Past dates: mostly approved, rejected, or cancelled
                $statusList = ['approved', 'approved', 'approved', 'rejected', 'cancelled'];
                $status = $statusList[array_rand($statusList)];
            } elseif ($meetingDate->gt($today)) {
                // Future dates: mostly pending or approved
                $statusList = ['pending', 'pending', 'approved', 'approved', 'rejected', 'cancelled'];
                $status = $statusList[array_rand($statusList)];
            } else {
                // Today: mix of approved and pending
                $statusList = ['approved', 'approved', 'pending'];
                $status = $statusList[array_rand($statusList)];
            }

            // Check conflict for approved bookings
            if ($status === 'approved') {
                $hasConflict = Book::hasConflict(
                    $room->id,
                    $meetingDate->format('Y-m-d'),
                    $endDate->format('Y-m-d'),
                    $startHour,
                    $finishHour
                );

                if ($hasConflict) {
                    continue; // Skip conflicting approved bookings
                }
            }

            $title = $meetingTitles[array_rand($meetingTitles)];
            $participantsCount = rand(3, $room->capacity > 0 ? $room->capacity : 15);

            $approvedBy = null;
            $approvedAt = null;
            $cancelledAt = null;
            $remarks = null;

            if ($status === 'approved') {
                $approvedBy = $admin->id;
                $approvedAt = $meetingDate->copy()->subHours(rand(1, 24));
            } elseif ($status === 'rejected') {
                $remarks = $rejectionReasons[array_rand($rejectionReasons)];
            } elseif ($status === 'cancelled') {
                $cancelledAt = $meetingDate->copy()->subHours(rand(2, 48));
            }

            Book::create([
                'room_id'      => $room->id,
                'requester_id' => $requester->id,
                'title'        => $title,
                'start_date'   => $meetingDate->format('Y-m-d'),
                'end_date'     => $endDate->format('Y-m-d'),
                'start_hour'   => $startHour,
                'finish_hour'  => $finishHour,
                'status'       => $status,
                'notes'        => 'Membutuhkan proyektor, papan tulis, dan konsumsi untuk ' . $participantsCount . ' orang.',
                'remarks'      => $remarks,
                'approved_by'  => $approvedBy,
                'approved_at'  => $approvedAt,
                'cancelled_at' => $cancelledAt,
                'pic_name'     => $requester->name,
                'participants' => $participantsCount,
            ]);

            $createdCount++;
        }

        $this->command->info("Berhasil meng-inject {$createdCount} data dummy booking rapat dari Juli hingga Agustus 2026.");
    }
}
