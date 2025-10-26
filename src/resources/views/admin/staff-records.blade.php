@extends('layouts.app')

@section('title', 'スタッフ別勤怠一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/records.css') }}">
@endsection

@section('content')

@include('components.admin-header')

<div class="app">
    <div class="records__list">
        <h1 class="page-title">{{$user->name}}さんの勤怠</h1>

        <div class="month-navigation">
            <a href="{{route('staff-record.list', ['id' => $user->id, 'month' => $currentMonth->copy()->subMonth()->format('Y-m')])}}" , class="month-button last-month">前月</a>
            <span class="current-month">{{ $currentMonth->format('Y/m') }}</span>
            <a href="{{route('staff-record.list', ['id' => $user->id, 'month' => $currentMonth->copy()->addMonth()->format('Y-m')])}}" , class="month-button next-month">翌月</a>
        </div>
        <table class="records__list__table">
            <thead>
                <tr class="table__row">
                    <th class="date">日付</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th class="detail">詳細</th>
                </tr>
            </thead>

            <tbody>
                @if(!$hasAttendance)
                <tr>
                    <td colspan="6" class="no-data">この月の勤怠データはありません</td>
                </tr>
                @else
                @foreach($dates as $date)
                @php
                $attendance = $attendancesByDate[$date->format('Y-m-d')] ?? null;
                @endphp
                <tr class="table__row">
                    <td>{{ $date->format('m/d') }}({{ $date->isoFormat('ddd') }})</td>
                    <td>{{ $attendance?->clock_in?->format('H:i') ?? '' }}</td>
                    <td>{{ $attendance?->clock_out?->format('H:i') ?? '' }}</td>
                    <td>{{ $attendance?->total_break ? gmdate('H:i', $attendance->total_break * 60) : '' }}</td>
                    <td>{{ $attendance?->total_work ? gmdate('H:i', $attendance->total_work * 60) : '' }}</td>
                    <td class="detail__link">
                        @if($attendance)
                        <a href="{{ route('admin.detail.record', $attendance->id) }}">詳細</a>
                        @else
                        <span>詳細</span>
                        @endif
                    </td>
                </tr>
                @endforeach
                @endif
            </tbody>
        </table>

        <form class="export-csv" method="POST" action="{{ route('admin.export',$user->id) }}">
            @csrf
            <input type="hidden" name="month" value="{{ $currentMonth->format('Y-m') }}">
            <button type="submit" class="export__button">CSV出力</button>
        </form>
    </div>
</div>
@endsection