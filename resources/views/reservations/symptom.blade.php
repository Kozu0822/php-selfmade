@extends('layouts.app')

@section('title', '症状選択')

@section('content')
<main class="reservation-main">
    <section class="panel reservation-panel symptom-panel">
        <h1 class="reservation-title reservation-title-left">{{ $device->name }}の修理のご相談</h1>

        @if (session('message'))
            <div class="success">{{ session('message') }}</div>
        @endif

        <form action="{{ route('reservations.symptom.store') }}" method="POST" class="symptom-choice-form">
            @csrf

            <select name="symptom_id" class="select-box">
                <option value="">症状をお選びください</option>
                @foreach ($symptoms as $symptom)
                    @php
                        $isOutOfStock = in_array($symptom->id, $outOfStockSymptomIds);
                    @endphp
                    <option value="{{ $symptom->id }}" @if ((string) old('symptom_id') === (string) $symptom->id) selected @endif @if ($isOutOfStock) disabled @endif>
                        {{ $symptom->name }}@if ($isOutOfStock)（在庫不足）@endif
                    </option>
                @endforeach
            </select>
            @error('symptom_id')<div class="error">{{ $message }}</div>@enderror

            <div class="reservation-actions">
                <a href="{{ route('reservations.create') }}" class="btn btn-muted">戻る</a>
                <button type="submit" class="btn btn-primary">日時選択画面へ</button>
            </div>
        </form>

        <form action="{{ route('reservations.symptom.ai') }}" method="POST" class="ai-box">
            @csrf
            <div class="ai-heading">
                <span class="ai-badge">AI診断</span>
                <span class="ai-title">症状が分からない場合</span>
            </div>
            <p class="ai-help">文章で相談すると、AIが近い症状を選択します。設定で解決できそうな内容は、簡単な案内を表示します。</p>

            @if (session('ai_advice'))
                <div class="ai-advice">
                    <div class="ai-advice-title">AI診断結果</div>
                    <p>{{ session('ai_advice') }}</p>
                    @if (session('ai_recommendation'))
                        <p>{{ session('ai_recommendation') }}</p>
                    @endif
                </div>
            @endif

            <textarea name="symptom_text" placeholder="例：バッテリーの減りが早いです">{{ old('symptom_text') }}</textarea>
            @error('symptom_text')<div class="error">{{ $message }}</div>@enderror
            <button type="submit" class="btn btn-ai">AI診断で選択</button>
        </form>
    </section>
</main>
@endsection
