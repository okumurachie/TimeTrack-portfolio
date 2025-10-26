<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\Correction;
use Carbon\Carbon;

class AttendanceDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            for ($i = -1; $i <= 1; $i++) {
                $month = Carbon::now()->addMonthsNoOverflow($i);
                $start = Carbon::create($month->year, $month->month, 1);
                $end = Carbon::create($month->year, $month->month, $month->daysInMonth);

                $attendances = collect();
                for ($date = $start->copy(); $date->lessThanOrEqualTo($end); $date->addDay()) {
                    if ($date->isWeekday() && (app()->environment('testing') || !$date->isToday())) {

                        $dateString = $date->toDateString();

                        $attendance = Attendance::create([
                            'user_id' => $user->id,
                            'work_date' => $dateString,
                            'clock_in' => $dateString . ' 09:00:00',
                            'clock_out' => $dateString . ' 18:00:00',
                            'total_break' => 60,
                            'total_work' => 480,
                            'is_on_break' => false,
                            'has_request' => false,
                        ]);

                        BreakTime::create([
                            'attendance_id' => $attendance->id,
                            'break_start' => $dateString . ' 12:00:00',
                            'break_end' => $dateString . ' 13:00:00',
                        ]);

                        $attendances->push($attendance);
                    }
                }

                $pastAttendances = $attendances;
                foreach ($pastAttendances->random(min(5, $pastAttendances->count())) as $attendance) {
                    $statuses = ['pending', 'approved'];
                    $reasons = ['遅延のため', '早退のため', '打刻漏れのため'];
                    $adminIds = [1, 2];
                    $status = $statuses[array_rand($statuses)];

                    $changes = [
                        'clock_in' => optional($attendance->clock_in)->copy()->addMinutes(rand(5, 15))->format('H:i'),
                        'clock_out' => optional($attendance->clock_out)->copy()->subMinutes(rand(5, 15))->format('H:i'),
                        'breaks' => [
                            ['start' => '12:00', 'end' => '12:30'],
                            ['start' => '15:00', 'end' => '15:30'],
                        ],
                    ];

                    Correction::create([
                        'attendance_id' => $attendance->id,
                        'user_id' => $user->id,
                        'status' => $status,
                        'reason' => $reasons[array_rand($reasons)],
                        'changes' => $changes,
                        'admin_id' => $status === 'approved' ? $adminIds[array_rand($adminIds)] : null,
                    ]);

                    if ($status === 'approved') {
                        $dateString = $attendance->work_date->toDateString();
                        $clockIn = Carbon::parse($dateString . ' ' . $changes['clock_in']);
                        $clockOut = Carbon::parse($dateString . ' ' . $changes['clock_out']);

                        BreakTime::where('attendance_id', $attendance->id)->delete();

                        foreach ($changes['breaks'] as $break) {
                            BreakTime::create([
                                'attendance_id' => $attendance->id,
                                'break_start' => $dateString . ' ' . $break['start'] . ':00',
                                'break_end' => $dateString . ' ' . $break['end'] . ':00',
                            ]);
                        }

                        $breakMinutes = 0;
                        foreach ($changes['breaks'] as $break) {
                            $start = Carbon::parse($dateString . ' ' . $break['start']);
                            $end = Carbon::parse($dateString . ' ' . $break['end']);
                            $breakMinutes += $start->diffInMinutes($end);
                        }
                        $workMinutes = $clockIn->diffInMinutes($clockOut);

                        $attendance->update([
                            'clock_in' => $clockIn,
                            'clock_out' => $clockOut,
                            'total_break' => $breakMinutes,
                            'total_work' => max(0, $workMinutes - $breakMinutes),
                        ]);
                    }

                    $attendance->update(['has_request' => true]);
                }
            }
        }
    }
}
