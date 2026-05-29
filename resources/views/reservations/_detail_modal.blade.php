<div class="modal-backdrop">
    <div class="modal">
        <h2 class="modal-title">予約詳細</h2>

        <dl>
            <div class="modal-row">
                <dt>予約ID</dt>
                <dd>{{ $reservation->id }}</dd>
            </div>
            @if (!empty($showCustomer))
                <div class="modal-row">
                    <dt>顧客名</dt>
                    <dd>{{ $reservation->user->name }}</dd>
                </div>
            @endif
            <div class="modal-row">
                <dt>端末</dt>
                <dd>{{ $reservation->device->name }}</dd>
            </div>
            <div class="modal-row">
                <dt>症状</dt>
                <dd>{{ $reservation->symptom->name }}</dd>
            </div>
            <div class="modal-row">
                <dt>予約日時</dt>
                <dd>{{ $reservation->timeSlot->slot_at->format('Y年n月j日 H:i') }}</dd>
            </div>
            <div class="modal-row">
                <dt>必要部品</dt>
                <dd>
                    @if ($reservation->parts->isEmpty())
                        なし
                    @else
                        <div class="stacked-text">
                            @foreach ($reservation->parts as $part)
                                <span>{{ $part->name }}</span>
                            @endforeach
                        </div>
                    @endif
                </dd>
            </div>
            <div class="modal-row">
                <dt>状態</dt>
                <dd><span class="{{ $reservation->statusClass() }}">{{ $reservation->statusLabel() }}</span></dd>
            </div>
        </dl>

        <div class="button-row">
            @if (!empty($canCancel) && !empty($cancelUrl))
                <form action="{{ $cancelUrl }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-danger btn-cancel">キャンセル</button>
                </form>
            @endif
            <a href="{{ $closeUrl }}" class="btn btn-primary">閉じる</a>
        </div>
    </div>
</div>
