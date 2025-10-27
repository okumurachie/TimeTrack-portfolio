<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Correction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CorrectionController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'pending');

        if (Auth::guard('admin')->check()) {
            $query = Correction::with(['user', 'attendance']);
        } else {
            $query = Correction::with(['user', 'attendance'])
                ->where('user_id', Auth::id());
        }

        if ($tab === 'pending') {
            $query->where('status', 'pending');
        } elseif ($tab === 'approved') {
            $query->where('status', 'approved');
        }

        $query = $query
            ->orderBy('user_id', 'asc')
            ->orderBy('attendance_id', 'asc');

        $corrections = $query->get();

        return view('request-list', compact('tab', 'corrections'));
    }

    public function show(Request $request, Correction $attendance_correct_request)
    {
        $correction = $attendance_correct_request->load(['attendance', 'user']);
        $user = $correction->user;
        $attendance = $correction->attendance;
        $changes = $correction->changes;
        $date = $request->query('date', $attendance->work_date->toDateString());
        $workDate = Carbon::parse($date);

        if (!empty($changes['breaks']) && is_array($changes['breaks'])) {
            $changes['breaks'] = collect($changes['breaks'])
                ->filter(function ($break) {
                    return is_array($break) && (
                        (!empty($break['start'] ?? null)) ||
                        (!empty($break['end'] ?? null))
                    );
                })
                ->values()
                ->toArray();
        }

        return view('admin.approve', compact('correction', 'user', 'attendance', 'changes', 'date', 'workDate'));
    }

    public function approve(Correction $attendance_correct_request)
    {
        DB::transaction(function () use ($attendance_correct_request) {
            $correction = $attendance_correct_request->load(['attendance', 'user']);

            $changes = $correction->changes;
            $attendance = $correction->attendance;
            $date = $attendance->work_date->toDateString();

            $clockIn = !empty($changes['clock_in']) ? Carbon::parse($date . ' ' . $changes['clock_in']) : $attendance->clock_in;
            $clockOut = !empty($changes['clock_out']) ? Carbon::parse($date . ' ' . $changes['clock_out']) : $attendance->clock_out;

            $attendance->breakTimes()->delete();
            if (!empty($changes['breaks'])) {
                $breaks = collect($changes['breaks'])->map(function ($break) use ($attendance) {
                    $workDate = Carbon::parse($attendance->work_date);
                    return [
                        'break_start' => !empty($break['start']) ? $workDate->copy()->setTimeFromTimeString($break['start']) : null,
                        'break_end' => !empty($break['end']) ? $workDate->copy()->setTimeFromTimeString($break['end']) : null,
                    ];
                })
                    ->filter(function ($break) {
                        return !empty($break['break_start']) && !empty($break['break_end']);
                    })
                    ->values()
                    ->toArray();
                if (!empty($breaks)) {
                    $attendance->breakTimes()->createMany($breaks);
                }
                $attendance->load('breakTimes');
            }

            $workMinutes = ($clockIn && $clockOut) ? $clockIn->diffInMinutes($clockOut) : 0;

            $breakMinutes = $attendance->breakTimes->sum(function ($break) {
                $start = $break->break_start ? Carbon::parse($break->break_start) : null;
                $end = $break->break_end ? Carbon::parse($break->break_end) : null;
                return ($start && $end) ? $start->diffInMinutes($end) : 0;
            });

            $attendance->update([
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'total_work' => max(0, $workMinutes - $breakMinutes),
                'total_break' => $breakMinutes,
            ]);

            $correction->update([
                'admin_id' => auth('admin')->id(),
                'status' => 'approved'
            ]);
        });
        return redirect()->route('correction.approval.show', $attendance_correct_request->id);
    }
}
