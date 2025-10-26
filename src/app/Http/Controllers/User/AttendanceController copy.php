<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\Correction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Requests\CorrectionRequest;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AttendanceController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $today = now()->toDateString();

        $todayAttendance = Attendance::where('user_id', $user->id)
            ->where('work_date', $today)
            ->first();

        return view('attendance', compact('todayAttendance'));
    }

    public function stamp(Request $request)
    {
        $user = Auth::user();
        $today = now()->toDateString();

        $attendance = Attendance::firstOrCreate(
            ['user_id' => $user->id, 'work_date' => $today],
            ['is_on_break' => false],
        );

        $action = $request->input('action');

        switch ($action) {
            case 'clock_in';
                if (!$attendance->clock_in) {
                    $attendance->update(['clock_in' => now()]);
                    return redirect()->route('attendance.index');
                }
                break;
            case 'break_start';
                if (!$attendance->is_on_break) {
                    $attendance->update(['is_on_break' => true]);
                    BreakTime::create([
                        'attendance_id' => $attendance->id,
                        'break_start' => now(),
                    ]);
                    return redirect()->route('attendance.index');
                }
                break;
            case 'break_end';
                if ($attendance->is_on_break) {
                    $attendance->update(['is_on_break' => false]);
                    $lastBreak = $attendance->breakTimes()->latest()->first();
                    if ($lastBreak && !$lastBreak->break_end) {
                        $lastBreak->update(['break_end' => now()]);
                    }
                    return redirect()->route('attendance.index');
                }
                break;
            case 'clock_out';
                if (!$attendance->clock_out) {
                    if ($attendance->is_on_break) {
                        $attendance->update(['is_on_break' => false]);
                        $lastBreak = $attendance->brakeTimes()->latest()->first();
                        if ($lastBreak && !$lastBreak->break_end) {
                            $lastBreak->update(['break_end' => now()]);
                        }
                    }

                    $attendance->update(['clock_out' => now()]);
                    $workMinutes = $attendance->clock_in->diffInMinutes($attendance->clock_out);
                    $breakMinutes = $attendance->breakTimes->sum(function ($break) {
                        return $break->break_end ? $break->break_start->diffInMinutes($break->break_end) : 0;
                    });

                    $attendance->total_work = $workMinutes - $breakMinutes;
                    $attendance->total_break = $breakMinutes;
                    $attendance->save();

                    return redirect()->route('attendance.index');
                }
                break;

            default:
                return redirect()->route('attendance.index')->with('error', '無効な操作です');
        }
        return redirect()->route('attendance.index')->with('error', 'すでに打刻済みです');
    }

    public function showMyRecord(Request $request)
    {
        $user = Auth::user();

        $month = $request->query('month', Carbon::now()->format('Y-m'));
        $currentMonth = Carbon::createFromFormat('Y-m', $month);

        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();

        $attendances = Attendance::where('user_id', $user->id)
            ->whereBetween('work_date', [$startOfMonth, $endOfMonth])
            ->orderBy('work_date')
            ->get();

        $hasAttendance = $attendances->isNotEmpty();
        $attendancesByDate = $attendances->keyBy(fn($attendance) => $attendance->work_date->format('Y-m-d'));
        $dates = CarbonPeriod::create($startOfMonth, $endOfMonth);

        return view('my-records', compact('dates', 'attendancesByDate', 'hasAttendance', 'currentMonth'));
    }

    public function detail(Request $request, $id)
    {
        $user = Auth::user();
        $attendance = Attendance::with(['user', 'corrections'])
            ->where('user_id', $user->id)
            ->findOrFail($id);
        $date = $request->query('date', $attendance->work_date->toDateString());
        $workDate = Carbon::parse($date);
        $breakTimes = BreakTime::where('attendance_id', $attendance->id)->get();
        $latestCorrection = $attendance->corrections()->latest()->first();

        $latestChanges = null;
        if ($latestCorrection) {
            $latestChanges = is_string($latestCorrection->changes)
                ? json_decode($latestCorrection->changes, true) ?? []
                : ($latestCorrection->changes ?? []);
            if (!empty($latestChanges['breaks'])) {
                $latestChanges['breaks'] = collect($latestChanges['breaks'])
                    ->filter(function ($break) {
                        return !empty($break['start']) && !empty($break['end']);
                    })
                    ->values()
                    ->toArray();

                if (empty($latestChanges['breaks'])) {
                    unset($latestChanges['breaks']);
                }
            }
        }

        if (!empty($latestChanges['breaks'])) {
            $breaks = $latestChanges['breaks'];
        } else {
            $breaks = $breakTimes
                ->filter(function ($break) {
                    return !empty($break->break_start) && !empty($break->break_end);
                })
                ->map(function ($break) {
                    return [
                        'start' => $break->break_start ? Carbon::parse($break->break_start)->format('H:i') : '',
                        'end' => $break->break_end ? Carbon::parse($break->break_end)->format('H:i') : '',
                    ];
                })
                ->values()
                ->toArray();
        }

        return view('detail', compact('user', 'attendance', 'workDate', 'breakTimes', 'latestCorrection', 'latestChanges', 'breaks'));
    }
    public function store(CorrectionRequest $request, $id)
    {
        $user = Auth::user();
        $attendance = Attendance::where('user_id', $user->id)->findOrFail($id);

        $rawBreaks = $request->input('breaks', []);
        $filteredBreaks = collect($rawBreaks)
            ->filter(function ($break) {
                return !empty($break['start']) && !empty($break['end']);
            })
            ->values()
            ->toArray();

        $changes = [
            'clock_in' => $request->input('clock_in'),
            'clock_out' => $request->input('clock_out'),
            'breaks' => $filteredBreaks,
        ];

        Correction::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'reason' => $request->input('reason'),
            'changes' => $changes,
        ]);

        $attendance->update(['has_request' => true]);

        return redirect()->route('user.detail.record', $attendance->id);
    }
}
