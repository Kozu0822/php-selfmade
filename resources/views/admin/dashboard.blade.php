@extends('layouts.app')

@section('title', '管理画面')

@section('content')
<main class="admin-shell">
    <section class="admin-panel">
        <nav class="admin-menu">
            <a href="{{ route('admin.dashboard', ['tab' => 'reservations']) }}" class="{{ $tab === 'reservations' ? 'active' : '' }}">予約管理</a>
            <a href="{{ route('admin.dashboard', ['tab' => 'time_slots']) }}" class="{{ $tab === 'time_slots' ? 'active' : '' }}">予約枠管理</a>
            <a href="{{ route('admin.dashboard', ['tab' => 'devices']) }}" class="{{ $tab === 'devices' ? 'active' : '' }}">端末管理</a>
            <a href="{{ route('admin.dashboard', ['tab' => 'symptoms']) }}" class="{{ $tab === 'symptoms' ? 'active' : '' }}">症状管理</a>
            <a href="{{ route('admin.dashboard', ['tab' => 'parts']) }}" class="{{ $tab === 'parts' ? 'active' : '' }}">部品管理</a>
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
                                        <div class="inline-actions">
                                            <a href="{{ route('admin.dashboard', ['tab' => 'reservations', 'reservation_id' => $reservation->id]) }}" class="btn btn-secondary btn-small">詳細を見る</a>
                                        </div>
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
            @elseif ($tab === 'devices')
                <div class="section-heading">
                    <h1 class="page-title">端末管理</h1>
                    <a href="{{ route('admin.dashboard', ['tab' => 'devices', 'add_device' => 1]) }}" class="btn btn-primary">新規追加</a>
                </div>

                @error('device')<div class="error" style="text-align: left; margin-bottom: 12px;">{{ $message }}</div>@enderror

                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>端末名</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($devices as $device)
                            <tr class="{{ $device->trashed() ? 'disabled-row' : '' }}">
                                <td>{{ $device->id }}</td>
                                <td>{{ $device->name }}</td>
                                <td>
                                    <div class="inline-actions">
                                        @if ($device->trashed())
                                            <span class="btn btn-muted btn-small" aria-disabled="true">編集</span>
                                            <form action="{{ route('admin.devices.restore', $device->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-primary btn-small">有効化</button>
                                            </form>
                                        @else
                                            <a href="{{ route('admin.dashboard', ['tab' => 'devices', 'edit_device' => $device->id]) }}" class="btn btn-secondary btn-small">編集</a>
                                            @if ((int) $device->active_reservations_count === 0)
                                                <form action="{{ route('admin.devices.destroy', $device) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="btn btn-danger btn-small">削除</button>
                                                </form>
                                            @else
                                                <span title="未対応の予約があるため削除できません。">
                                                    <button type="button" class="btn btn-muted btn-small" disabled>削除</button>
                                                </span>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if ($devices->hasPages())
                    <div class="pagination">
                        @if ($devices->onFirstPage())
                            <span class="disabled">前へ</span>
                        @else
                            <a href="{{ $devices->previousPageUrl() }}">前へ</a>
                        @endif

                        @for ($page = 1; $page <= $devices->lastPage(); $page++)
                            @if ($page === $devices->currentPage())
                                <span class="active">{{ $page }}</span>
                            @else
                                <a href="{{ $devices->url($page) }}">{{ $page }}</a>
                            @endif
                        @endfor

                        @if ($devices->hasMorePages())
                            <a href="{{ $devices->nextPageUrl() }}">次へ</a>
                        @else
                            <span class="disabled">次へ</span>
                        @endif
                    </div>
                @endif
            @elseif ($tab === 'symptoms')
                <div class="section-heading">
                    <h1 class="page-title">症状管理</h1>
                    <a href="{{ route('admin.dashboard', ['tab' => 'symptoms', 'add_symptom' => 1]) }}" class="btn btn-primary">新規追加</a>
                </div>

                @error('symptom')<div class="error" style="text-align: left; margin-bottom: 12px;">{{ $message }}</div>@enderror

                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>症状名</th>
                            <th>対応端末</th>
                            <th>状態</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($symptoms as $symptom)
                            <tr class="{{ $symptom->trashed() ? 'disabled-row' : '' }}">
                                <td>{{ $symptom->id }}</td>
                                <td>{{ $symptom->name }}</td>
                                <td>
                                    <div class="stacked-text">
                                        @foreach ($symptom->devices as $device)
                                            <span>{{ $device->name }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td>{{ $symptom->trashed() ? '対応不可' : '対応可' }}</td>
                                <td>
                                    <div class="inline-actions">
                                        @if ($symptom->trashed())
                                            <span class="btn btn-muted btn-small" aria-disabled="true">編集</span>
                                            <form action="{{ route('admin.symptoms.restore', $symptom->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-primary btn-small">有効化</button>
                                            </form>
                                        @else
                                            <a href="{{ route('admin.dashboard', ['tab' => 'symptoms', 'edit_symptom' => $symptom->id]) }}" class="btn btn-secondary btn-small">編集</a>
                                            @if ((int) $symptom->active_reservations_count === 0)
                                                <form action="{{ route('admin.symptoms.destroy', $symptom->id) }}" method="POST" onsubmit="return confirm('この症状は対応不可になり、顧客の予約画面に表示されなくなります。よろしいですか？');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-danger btn-small">削除</button>
                                                </form>
                                            @else
                                                <span title="未対応の予約があるため削除できません。">
                                                    <button type="button" class="btn btn-muted btn-small" disabled>削除</button>
                                                </span>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if ($symptoms->hasPages())
                    <div class="pagination">
                        @if ($symptoms->onFirstPage())
                            <span class="disabled">前へ</span>
                        @else
                            <a href="{{ $symptoms->previousPageUrl() }}">前へ</a>
                        @endif

                        @for ($page = 1; $page <= $symptoms->lastPage(); $page++)
                            @if ($page === $symptoms->currentPage())
                                <span class="active">{{ $page }}</span>
                            @else
                                <a href="{{ $symptoms->url($page) }}">{{ $page }}</a>
                            @endif
                        @endfor

                        @if ($symptoms->hasMorePages())
                            <a href="{{ $symptoms->nextPageUrl() }}">次へ</a>
                        @else
                            <span class="disabled">次へ</span>
                        @endif
                    </div>
                @endif
            @else
                <div class="section-heading">
                    <h1 class="page-title">部品管理</h1>
                    <a href="{{ route('admin.dashboard', ['tab' => 'parts', 'add_part' => 1]) }}" class="btn btn-primary">新規追加</a>
                </div>

                @error('part')<div class="error" style="text-align: left; margin-bottom: 12px;">{{ $message }}</div>@enderror

                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>対応機種</th>
                            <th>部品名</th>
                            <th>対応症状</th>
                            <th>在庫数</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($parts as $part)
                            <tr class="{{ $part->trashed() ? 'disabled-row' : '' }}">
                                <td>{{ $part->id }}</td>
                                <td>{{ $part->device ? $part->device->name : '共通' }}</td>
                                <td>{{ $part->name }}</td>
                                <td>
                                    <div class="stacked-text">
                                        @foreach ($part->symptoms as $symptom)
                                            <span>{{ $symptom->name }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td>{{ $part->stock }}</td>
                                <td>
                                    <div class="inline-actions">
                                        @if ($part->trashed())
                                            <span class="btn btn-muted btn-small" aria-disabled="true">編集</span>
                                            <form action="{{ route('admin.parts.restore', $part->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-primary btn-small">有効化</button>
                                            </form>
                                        @else
                                            <a href="{{ route('admin.dashboard', ['tab' => 'parts', 'edit_part' => $part->id]) }}" class="btn btn-secondary btn-small">編集</a>
                                            @if ((int) $part->active_reservations_count === 0)
                                                <form action="{{ route('admin.parts.destroy', $part) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="btn btn-danger btn-small">削除</button>
                                                </form>
                                            @else
                                                <span title="未対応の予約があるため削除できません。">
                                                    <button type="button" class="btn btn-muted btn-small" disabled>削除</button>
                                                </span>
                                            @endif
                                        @endif
                                    </div>
                                </td>
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

@if ($tab === 'devices' && request('add_device'))
    <div class="modal-backdrop">
        <div class="modal">
            <h2 class="modal-title">端末を追加</h2>

            <form action="{{ route('admin.devices.store') }}" method="POST">
                @csrf

                <div class="form-group">
                    <label for="device_name">端末名</label>
                    <input type="text" name="name" id="device_name" class="form-control" value="{{ old('name') }}">
                    @error('name')<div class="error" style="text-align: left;">{{ $message }}</div>@enderror
                </div>

                <div class="button-row">
                    <button type="submit" class="btn btn-primary">追加する</button>
                    <a href="{{ route('admin.dashboard', ['tab' => 'devices']) }}" class="btn btn-secondary">閉じる</a>
                </div>
            </form>
        </div>
    </div>
@endif

@if ($tab === 'devices' && $selectedDevice)
    <div class="modal-backdrop">
        <div class="modal">
            <h2 class="modal-title">端末を編集</h2>

            <form action="{{ route('admin.devices.update', $selectedDevice) }}" method="POST">
                @csrf

                <div class="form-group">
                    <label for="edit_device_name">端末名</label>
                    <input type="text" name="name" id="edit_device_name" class="form-control" value="{{ old('name', $selectedDevice->name) }}">
                    @error('name')<div class="error" style="text-align: left;">{{ $message }}</div>@enderror
                </div>

                <div class="button-row">
                    <button type="submit" class="btn btn-primary">更新する</button>
                    <a href="{{ route('admin.dashboard', ['tab' => 'devices']) }}" class="btn btn-secondary">閉じる</a>
                </div>
            </form>
        </div>
    </div>
@endif

@if ($tab === 'symptoms' && request('add_symptom'))
    <div class="modal-backdrop">
        <div class="modal">
            <h2 class="modal-title">症状を追加</h2>

            <form action="{{ route('admin.symptoms.store') }}" method="POST">
                @csrf

                <div class="form-group">
                    <label for="symptom_name">症状名</label>
                    <input type="text" name="name" id="symptom_name" class="form-control" value="{{ old('name') }}">
                    @error('name')<div class="error" style="text-align: left;">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label>対応端末</label>
                    <div class="checkbox-list">
                        @foreach ($allDevices as $device)
                            <label>
                                <input type="checkbox" name="device_ids[]" value="{{ $device->id }}" @checked(in_array($device->id, old('device_ids', [])))>
                                {{ $device->name }}
                            </label>
                        @endforeach
                    </div>
                    @error('device_ids')<div class="error" style="text-align: left;">{{ $message }}</div>@enderror
                </div>

                <div class="button-row">
                    <button type="submit" class="btn btn-primary">追加する</button>
                    <a href="{{ route('admin.dashboard', ['tab' => 'symptoms']) }}" class="btn btn-secondary">閉じる</a>
                </div>
            </form>
        </div>
    </div>
@endif

@if ($tab === 'symptoms' && $selectedSymptom)
    @php
        $selectedDeviceIds = old('device_ids', $selectedSymptom->devices->pluck('id')->toArray());
    @endphp
    <div class="modal-backdrop">
        <div class="modal">
            <h2 class="modal-title">症状を編集</h2>

            <form action="{{ route('admin.symptoms.update', $selectedSymptom->id) }}" method="POST">
                @csrf

                <div class="form-group">
                    <label for="edit_symptom_name">症状名</label>
                    <input type="text" name="name" id="edit_symptom_name" class="form-control" value="{{ old('name', $selectedSymptom->name) }}">
                    @error('name')<div class="error" style="text-align: left;">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label>対応端末</label>
                    <div class="checkbox-list">
                        @foreach ($allDevices as $device)
                            <label>
                                <input type="checkbox" name="device_ids[]" value="{{ $device->id }}" @checked(in_array($device->id, $selectedDeviceIds))>
                                {{ $device->name }}
                            </label>
                        @endforeach
                    </div>
                    @error('device_ids')<div class="error" style="text-align: left;">{{ $message }}</div>@enderror
                </div>

                <div class="button-row">
                    <button type="submit" class="btn btn-primary">更新する</button>
                    <a href="{{ route('admin.dashboard', ['tab' => 'symptoms']) }}" class="btn btn-secondary">閉じる</a>
                </div>
            </form>
        </div>
    </div>
@endif

@if ($tab === 'parts' && request('add_part'))
    <div class="modal-backdrop">
        <div class="modal">
            <h2 class="modal-title">部品を追加</h2>

            <form action="{{ route('admin.parts.store') }}" method="POST">
                @csrf

                <div class="form-group">
                    <label for="part_device_id">対応端末</label>
                    <select name="device_id" id="part_device_id" class="form-control">
                        <option value="">選択してください</option>
                        @foreach ($allDevices as $device)
                            <option value="{{ $device->id }}" @selected((string) old('device_id') === (string) $device->id)>{{ $device->name }}</option>
                        @endforeach
                    </select>
                    @error('device_id')<div class="error" style="text-align: left;">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label for="part_name">部品名</label>
                    <input type="text" name="name" id="part_name" class="form-control" value="{{ old('name') }}">
                    @error('name')<div class="error" style="text-align: left;">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label for="part_stock">在庫数</label>
                    <input type="number" name="stock" id="part_stock" class="form-control" min="0" value="{{ old('stock', 0) }}">
                    @error('stock')<div class="error" style="text-align: left;">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label>対応症状</label>
                    <div class="checkbox-list">
                        @foreach ($allSymptoms as $symptom)
                            @if (!$symptom->trashed())
                                <label>
                                    <input type="checkbox" name="symptom_ids[]" value="{{ $symptom->id }}" @checked(in_array($symptom->id, old('symptom_ids', [])))>
                                    {{ $symptom->name }}
                                </label>
                            @endif
                        @endforeach
                    </div>
                    @error('symptom_ids')<div class="error" style="text-align: left;">{{ $message }}</div>@enderror
                </div>

                <div class="button-row">
                    <button type="submit" class="btn btn-primary">追加する</button>
                    <a href="{{ route('admin.dashboard', ['tab' => 'parts']) }}" class="btn btn-secondary">閉じる</a>
                </div>
            </form>
        </div>
    </div>
@endif

@if ($tab === 'parts' && $selectedPart)
    @php
        $selectedSymptomIds = old('symptom_ids', $selectedPart->symptoms->pluck('id')->toArray());
    @endphp
    <div class="modal-backdrop">
        <div class="modal">
            <h2 class="modal-title">部品を編集</h2>

            <form action="{{ route('admin.parts.update', $selectedPart) }}" method="POST">
                @csrf

                <div class="form-group">
                    <label for="edit_part_device_id">対応端末</label>
                    <select name="device_id" id="edit_part_device_id" class="form-control">
                        <option value="">選択してください</option>
                        @foreach ($allDevices as $device)
                            <option value="{{ $device->id }}" @selected((string) old('device_id', $selectedPart->device_id) === (string) $device->id)>{{ $device->name }}</option>
                        @endforeach
                    </select>
                    @error('device_id')<div class="error" style="text-align: left;">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label for="edit_part_name">部品名</label>
                    <input type="text" name="name" id="edit_part_name" class="form-control" value="{{ old('name', $selectedPart->name) }}">
                    @error('name')<div class="error" style="text-align: left;">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label for="edit_part_stock">在庫数</label>
                    <input type="number" name="stock" id="edit_part_stock" class="form-control" min="0" value="{{ old('stock', $selectedPart->stock) }}">
                    @error('stock')<div class="error" style="text-align: left;">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label>対応症状</label>
                    <div class="checkbox-list">
                        @foreach ($allSymptoms as $symptom)
                            @if (!$symptom->trashed() || in_array($symptom->id, $selectedSymptomIds))
                                <label>
                                    <input type="checkbox" name="symptom_ids[]" value="{{ $symptom->id }}" @checked(in_array($symptom->id, $selectedSymptomIds))>
                                    {{ $symptom->name }}{{ $symptom->trashed() ? '（停止中）' : '' }}
                                </label>
                            @endif
                        @endforeach
                    </div>
                    @error('symptom_ids')<div class="error" style="text-align: left;">{{ $message }}</div>@enderror
                </div>

                <div class="button-row">
                    <button type="submit" class="btn btn-primary">更新する</button>
                    <a href="{{ route('admin.dashboard', ['tab' => 'parts']) }}" class="btn btn-secondary">閉じる</a>
                </div>
            </form>
        </div>
    </div>
@endif

@if ($selectedReservation)
    @include('reservations._detail_modal', [
        'reservation' => $selectedReservation,
        'showCustomer' => true,
        'canCancel' => $selectedReservation->canBeCancelledByAdmin(),
        'cancelUrl' => route('admin.reservations.cancel', $selectedReservation),
        'closeUrl' => route('admin.dashboard', ['tab' => $tab]),
    ])
@endif
@endsection
