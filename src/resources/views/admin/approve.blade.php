@extends('layouts.app')

@section('title', '管理者修正申請承認画面')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/detail.css') }}">
@endsection

@section('content')

@include('components.admin-header')

<div class="app approve-page">
    <div class="correct__form__content">
        <h1 class="page-title">勤怠詳細</h1>

        <form action="{{ route('correction.approval', $correction->id) }}" class="correct__form" method="post">
            @csrf
            <table class="form__table">
                <tr class="table__row">
                    <td>
                        <label class="input__label">名前</label>
                        <div class="user-name">
                            <span>{{$user->name}}</span>
                        </div>
                    </td>
                </tr>
                <tr class="table__row">
                    <td>
                        <label class="input__label">日付</label>
                        <div class="work_date">
                            <span class="work_date-y">{{ $workDate->format('Y年') }}</span>
                            <span class="work_date-m-d">{{ $workDate->format('n月j日') }}</span>
                        </div>
                    </td>
                </tr>
                <tr class="table__row">
                    <td>
                        <label class="input__label ">出勤・退勤</label>
                        <div class="input__space">
                            <div class="input__group">
                                <input type="text" name="clock_in" class="clock_in" value="{{ old('clock_in', isset($changes['clock_in']) ? \Carbon\Carbon::parse($changes['clock_in'])->format('H:i') : '') }}" readonly>
                                <span class="tilde-mark">〜</span>
                                <input type="text" name="clock_out" class="clock_out" value="{{ old('clock_out', isset($changes['clock_out']) ? \Carbon\Carbon::parse($changes['clock_out'])->format('H:i') : '') }}" readonly>
                            </div>
                        </div>
                    </td>
                </tr>
                @foreach($changes['breaks'] ?? [] as $i => $break)
                <tr class="table__row">
                    <td>
                        <label class="input__label" for="breaks-{{ $i }}-start">
                            {{ $i === 0 ? '休憩' : '休憩' . ($i + 1) }}
                        </label>
                        <div class="input__space">
                            <div class="input__group">
                                <input id="breaks-{{ $i }}-start" type="text" name="breaks[{{ $i }}][start]" class="clock_in" value="{{$break['start'] ?? ''}}" readonly>
                                <span class="tilde-mark" aria-hidden="true">〜</span>
                                <input id="breaks-{{ $i }}-end" type="text" name="breaks[{{ $i }}][end]" class="clock_out" value="{{$break['end'] ?? ''}}" readonly>
                            </div>
                        </div>
                    </td>
                </tr>
                @endforeach

                <tr class="table__row">
                    <td>
                        <label class="input__label">備考</label>
                        <div class="textarea__space">
                            <textarea name="reason" readonly>{{old('reason', $correction->reason)}}</textarea>
                        </div>
                    </td>
                </tr>
            </table>
            <div class="form__button">
                @if($correction->status === 'pending')
                <button class="form__button__submit">承認</button>
                @else
                <p class="approved">承認済み</p>
                @endif
            </div>
        </form>
    </div>
</div>
@endsection