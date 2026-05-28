@extends('layouts.app')

@section('title', '管理画面')

@section('content')
<main class="admin-shell">
    <section class="admin-panel">
        <nav class="admin-menu">
            <a href="{{ route('admin.dashboard', ['tab' => 'reservations']) }}" class="{{ $tab === 'reservations' ? 'active' : '' }}">予約管理</a>
            <a href="{{ route('admin.dashboard', ['tab' => 'time_slots']) }}" class="{{ $tab === 'time_slots' ? 'active' : '' }}">予約枠管理</a>
            <a href="{{ route('admin.dashboard', ['tab' => 'parts']) }}" class="{{ $tab === 'parts' ? 'active' : '' }}">部品在庫</a>
        </nav>

        <div class="admin-content">
            @if ($tab === 'reservations')
                <h1 class="page-title">予約一覧</h1>

                @if ($reservations->isEmpty())
                    <div class="empty">予約はありません。</div>
                @else
                    <table class="table">
                        <thead>
                            <tr>
                                <th>予約ID</th>
                                <th>名前</th>
                                <th>端末</th>
                                <th>症状</th>
                                <th>予約日時</th>
                                <th>状態</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reservations as $reservation)
                                <tr>
                                    <td>{{ $reservation->id }}</td>
                                    <td>{{ $reservation->user->name }}</td>
                                    <td>{{ $reservation->device->name }}</td>
                                    <td>{{ $reservation->symptom->name }}</td>
                                    <td>{{ $reservation->timeSlot->slot_at->format('Y年n月j日 H:i') }}</td>
                                    <td>
                                        <span class="{{ $reservation->statusClass() }}">{{ $reservation->statusLabel() }}</span>
                                    </td>
                                    <td>
                                        @if ($reservation->canBeCancelledByAdmin())
                                            <form action="{{ route('admin.reservations.cancel', $reservation) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-danger btn-cancel">キャンセル</button>
                                            </form>
                                        @else
                                            <button type="button" class="btn btn-muted btn-cancel" disabled>キャンセル</button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if ($reservations->hasPages())
                        <div class="pagination">
                            @if ($reservations->onFirstPage())
                                <span class="disabled">前へ</span>
                            @else
                                <a href="{{ $reservations->previousPageUrl() }}">前へ</a>
                            @endif

                            @for ($page = 1; $page <= $reservations->lastPage(); $page++)
                                @if ($page === $reservations->currentPage())
                                    <span class="active">{{ $page }}</span>
                                @else
                                    <a href="{{ $reservations->url($page) }}">{{ $page }}</a>
                                @endif
                            @endfor

                            @if ($reservations->hasMorePages())
                                <a href="{{ $reservations->nextPageUrl() }}">次へ</a>
                            @else
                                <span class="disabled">次へ</span>
                            @endif
                        </div>
                    @endif
                @endif
            @elseif ($tab === 'time_slots')
                <div class="section-heading">
                    <h1 class="page-title">予約枠管理</h1>
                    <a href="{{ route('admin.dashboard', ['tab' => 'time_slots', 'add_time_slot' => 1]) }}" class="btn btn-primary">新規追加</a>
                </div>

                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>予約日時</th>
                            <th>開放状態</th>
                            <th>予約状態</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($timeSlots as $timeSlot)
                            @php
                                $activeReservation = $timeSlot->reservations->whereIn('status', ['pending', 'no_show'])->first();
                            @endphp
                            <tr>
                                <td>{{ $timeSlot->id }}</td>
                                <td>{{ $timeSlot->slot_at->format('Y年n月j日 H:i') }}</td>
                                <td>{{ $timeSlot->is_open ? '開放中' : '閉鎖中' }}</td>
                                <td>
                                    @if ($activeReservation)
                                        <span class="{{ $activeReservation->statusClass() }}">{{ $activeReservation->statusLabel() }}</span>
                                    @else
                                        未予約
                                    @endif
                                </td>
                                <td>
                                    <div class="inline-actions">
                                        @if ($timeSlot->is_reserved && $activeReservation)
                                            <a href="{{ route('admin.dashboard', ['tab' => 'time_slots', 'reservation_id' => $activeReservation->id]) }}" class="btn btn-secondary btn-small">詳細を見る</a>
                                        @else
                                            <form action="{{ route('admin.time_slots.toggle', $timeSlot) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-secondary btn-small">{{ $timeSlot->is_open ? '閉鎖' : '開放' }}</button>
                                            </form>
                                            <form action="{{ route('admin.time_slots.destroy', $timeSlot) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-danger btn-small">削除</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if ($timeSlots->hasPages())
                    <div class="pagination">
                        @if ($timeSlots->onFirstPage())
                            <span class="disabled">前へ</span>
                        @else
                            <a href="{{ $timeSlots->previousPageUrl() }}">前へ</a>
                        @endif

                        @for ($page = 1; $page <= $timeSlots->lastPage(); $page++)
                            @if ($page === $timeSlots->currentPage())
                                <span class="active">{{ $page }}</span>
                            @else
                                <a href="{{ $timeSlots->url($page) }}">{{ $page }}</a>
                            @endif
                        @endfor

                        @if ($timeSlots->hasMorePages())
                            <a href="{{ $timeSlots->nextPageUrl() }}">次へ</a>
                        @else
                            <span class="disabled">次へ</span>
                        @endif
                    </div>
                @endif
            @else
                <h1 class="page-title">部品在庫一覧</h1>

                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>対応機種</th>
                            <th>部品名</th>
                            <th>在庫数</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($parts as $part)
                            <tr>
                                <td>{{ $part->id }}</td>
                                <td>{{ $part->device ? $part->device->name : '共通' }}</td>
                                <td>{{ $part->name }}</td>
                                <td>{{ $part->stock }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if ($parts->hasPages())
                    <div class="pagination">
                        @if ($parts->onFirstPage())
                            <span class="disabled">前へ</span>
                        @else
                            <a href="{{ $parts->previousPageUrl() }}">前へ</a>
                        @endif

                        @for ($page = 1; $page <= $parts->lastPage(); $page++)
                            @if ($page === $parts->currentPage())
                                <span class="active">{{ $page }}</span>
                            @else
                                <a href="{{ $parts->url($page) }}">{{ $page }}</a>
                            @endif
                        @endfor

                        @if ($parts->hasMorePages())
                            <a href="{{ $parts->nextPageUrl() }}">次へ</a>
                        @else
                            <span class="disabled">次へ</span>
                        @endif
                    </div>
                @endif
            @endif
        </div>
    </section>
</main>

@if ($tab === 'time_slots' && request('add_time_slot'))
    <div class="modal-backdrop">
        <div class="modal">
            <h2 class="modal-title">予約枠を追加</h2>

            @error('slot_at')<div class="error" style="text-align: left; margin-bottom: 12px;">{{ $message }}</div>@enderror

            <form action="{{ route('admin.time_slots.store') }}" method="POST">
                @csrf

                <div class="form-group">
                    <label for="slot_date">日付</label>
                    <input type="date" name="slot_date" id="slot_date" class="form-control" value="{{ old('slot_date') }}">
                    @error('slot_date')<div class="error" style="text-align: left;">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label for="slot_time">時間</label>
                    <input type="time" name="slot_time" id="slot_time" class="form-control" value="{{ old('slot_time') }}">
                    @error('slot_time')<div class="error" style="text-align: left;">{{ $message }}</div>@enderror
                </div>

                <div class="button-row">
                    <button type="submit" class="btn btn-primary">追加する</button>
                    <a href="{{ route('admin.dashboard', ['tab' => 'time_slots']) }}" class="btn btn-secondary">閉じる</a>
                </div>
            </form>
        </div>
    </div>
@endif

@if ($tab === 'time_slots' && $selectedReservation)
    <div class="modal-backdrop">
        <div class="modal">
            <h2 class="modal-title">予約詳細</h2>

            <dl>
                <div class="modal-row">
                    <dt>予約ID</dt>
                    <dd>{{ $selectedReservation->id }}</dd>
                </div>
                <div class="modal-row">
                    <dt>顧客名</dt>
                    <dd>{{ $selectedReservation->user->name }}</dd>
                </div>
                <div class="modal-row">
                    <dt>端末</dt>
                    <dd>{{ $selectedReservation->device->name }}</dd>
                </div>
                <div class="modal-row">
                    <dt>症状</dt>
                    <dd>{{ $selectedReservation->symptom->name }}</dd>
                </div>
                <div class="modal-row">
                    <dt>予約日時</dt>
                    <dd>{{ $selectedReservation->timeSlot->slot_at->format('Y年n月j日 H:i') }}</dd>
                </div>
                <div class="modal-row">
                    <dt>状態</dt>
                    <dd>
                        <span class="{{ $selectedReservation->statusClass() }}">{{ $selectedReservation->statusLabel() }}</span>
                    </dd>
                </div>
            </dl>

            <div class="button-row">
                <a href="{{ route('admin.dashboard', ['tab' => 'time_slots']) }}" class="btn btn-primary">閉じる</a>
            </div>
        </div>
    </div>
@endif
@endsection
