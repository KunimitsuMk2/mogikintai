@extends('layouts.app')

@section('title', 'スタッフ一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/staff_list.css') }}">
@endsection

@section('content')
<div class="admin-staff-list__content">
    <h1 class="admin-staff-list__heading">スタッフ一覧</h1>
    
    <!-- スタッフ一覧テーブル -->
    <div class="admin-staff-list__table-container">
        <table class="admin-staff-list__table">
            <thead>
                <tr>
                    <th class="admin-staff-list__th">名前</th>
                    <th class="admin-staff-list__th">メールアドレス</th>
                    <th class="admin-staff-list__th">月次勤怠</th>
                </tr>
            </thead>
            <tbody>
                @forelse($staffUsers as $user)
                <tr>
                    <td class="admin-staff-list__td">{{ $user->name }}</td>
                    <td class="admin-staff-list__td">{{ $user->email }}</td>
                    <td class="admin-staff-list__td">
                        <a href="{{ route('admin.attendance.staff', $user->id) }}" class="admin-staff-list__detail-button">詳細</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="admin-staff-list__td admin-staff-list__td--empty">
                        スタッフが登録されていません
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection