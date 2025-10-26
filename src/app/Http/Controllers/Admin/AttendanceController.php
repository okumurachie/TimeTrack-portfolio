<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CorrectionRequest;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\Correction;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $users = User::all();
        $date = $request->query('date', now()->toDateString());
        $workDate = Carbon::parse($date);
        $workDateLabel = Carbon::parse($date)->toJapaneseDate();

        $attendances = Attendance::whereDate('work_date', $workDate)
            ->get()
            ->keyBy('user_id');

        $hasAttendance = $attendances->isNotEmpty();
        return view('admin.index', compact('users', 'workDateLabel', 'workDate', 'attendances', 'hasAttendance'));
    }

    public function staffList(Request $request)
    {
        $users = User::all();
        return view('admin.staff-list', compact('users'));
    }

    public function showStaffRecord(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $month = $request->query('month', Carbon::now()->format('Y-m'));
        try {
            $currentMonth = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } catch (\Exception $error) {
            $currentMonth = Carbon::now()->startOfMonth();
        }

        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();

        $attendances = Attendance::where('user_id', $user->id)
            ->whereBetween('work_date', [$startOfMonth, $endOfMonth])
            ->orderBy('work_date')
            ->get();

        $hasAttendance = $attendances->isNotEmpty();
        $attendancesByDate = $attendances->keyBy(fn($attendance) => $attendance->work_date->format('Y-m-d'));

        $dates = CarbonPeriod::create($startOfMonth, $endOfMonth);

        return view('admin.staff-records', compact('user', 'dates', 'attendancesByDate', 'hasAttendance', 'currentMonth'));
    }

    public function export(Request $request, $id)
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $user = User::findOrFail($id);
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        try {
            $currentMonth = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } catch (\Exception $error) {
            $currentMonth = Carbon::now()->startOfMonth();
        }

        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();

        $attendances = Attendance::where('user_id', $user->id)
            ->whereBetween('work_date', [$startOfMonth, $endOfMonth])
            ->orderBy('work_date')
            ->get();

        $filename = "{$user->name}_{$month}_attendance.csv";
        $asciiFilename = preg_replace('/[^\x20-\x7e]/', '_', $filename);

        $response = new StreamedResponse(function () use ($attendances, $user, $currentMonth, $startOfMonth, $endOfMonth) {
            $createCsvFile = fopen('php://output', 'w');

            stream_filter_prepend($createCsvFile, 'convert.iconv.utf-8/cp932//TRANSLIT');

            fputcsv($createCsvFile, [$user->name . ' ' . $currentMonth->format('Y年n月') . '勤怠一覧']);

            if ($attendances->isEmpty()) {
                fputcsv($createCsvFile, ['この月の勤怠データはありません']);
                fclose($createCsvFile);
                return;
            }

            fputcsv($createCsvFile, ['日付', '出勤', '退勤', '休憩', '合計']);

            $attendanceByDate = $attendances->keyBy(fn($attendance) => $attendance->work_date->toDateString());

            $date = $startOfMonth->copy();
            while ($date->lte($endOfMonth)) {
                $attendance = $attendanceByDate->get($date->toDateString());

                fputcsv($createCsvFile, [
                    $date->format('m/d') . '(' . $date->isoFormat('ddd') . ')',
                    optional($attendance?->clock_in)?->format('H:i') ?: '',
                    optional($attendance?->clock_out)?->format('H:i') ?: '',
                    $attendance && $attendance->total_break ? gmdate('H:i', $attendance->total_break * 60) : '',
                    $attendance && $attendance->total_work ? gmdate('H:i', $attendance->total_work * 60) : '',
                ]);

                $date->addDay();
            }

            fclose($createCsvFile);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=Shift_JIS');
        $disposition = "attachment; filename=\"{$asciiFilename}\"; filename*=UTF-8''" . rawurlencode($filename);
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    public function detail(Request $request, Attendance $attendance)
    {
        $attendance->load(['user', 'corrections']);
        $user = $attendance->user;
        $date = $request->query('date', $attendance->work_date->toDateString());
        $workDate = Carbon::parse($date);
        $breakTimes = BreakTime::where('attendance_id', $attendance->id)->get();
        $latestCorrection = $attendance->corrections()->latest()->first();

        return view('admin.detail', compact('user', 'attendance', 'workDate', 'breakTimes', 'latestCorrection'));
    }

    public function adminUpdate(CorrectionRequest $request, $id)
    {
        DB::transaction(function () use ($request, $id) {
            $attendance = Attendance::with('breakTimes')->findOrFail($id);
            $date = $attendance->work_date->toDateString();

            $clockIn = $request->input('clock_in') ? Carbon::parse($date . ' ' . $request->input('clock_in')) : null;
            $clockOut = $request->input('clock_out') ? Carbon::parse($date . ' ' . $request->input('clock_out')) : null;

            $attendance->breakTimes()->delete();

            $correctionBreaks = collect($request->input('breaks', []))
                ->filter(function ($break) {
                    return is_array($break) && !empty($break['start']) && !empty($break['end']);
                })
                ->values();

            $breaksForDB = $correctionBreaks->map(function ($break) use ($date) {
                return [
                    'break_start' => $date . ' ' . $break['start'] . ':00',
                    'break_end' => $date . ' ' . $break['end'] . ':00',
                ];
            })
                ->toArray();

            if (!empty($breaksForDB)) {
                $attendance->breakTimes()->createMany($breaksForDB);
            }

            $attendance->load('breakTimes');

            $workMinutes = ($clockIn && $clockOut) ? $clockIn->diffInMinutes($clockOut) : 0;
            $breakMinutes = $attendance->breakTimes->sum(function ($break) {
                $start = $break->break_start ? Carbon::parse($break->break_start) : null;
                $end = $break->break_end ? Carbon::parse($break->break_end) : null;
                return ($start && $end) ? $start->diffInMinutes($end) : 0;
            });

            $changes = [
                'clock_in' => $request->input('clock_in'),
                'clock_out' => $request->input('clock_out'),
                'breaks' => $correctionBreaks->map(function ($break) {
                    return [
                        'start' => $break['start'],
                        'end'   => $break['end'],
                    ];
                })->toArray(),
            ];

            $attendance->update([
                'clock_in'  => $clockIn,
                'clock_out' => $clockOut,
                'total_work' => max(0, $workMinutes - $breakMinutes),
                'total_break' => $breakMinutes,
                'has_request' => true,
            ]);

            Correction::create([
                'attendance_id' => $attendance->id,
                'user_id' => $attendance->user->id,
                'admin_id' => auth('admin')->id(),
                'reason' => $request->input('reason'),
                'status' => 'approved',
                'changes' => $changes,
            ]);
        });
        return redirect()->route('admin.detail.record', $id);
    }
}
