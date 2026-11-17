@extends('layouts.admin')
@section('title', 'Monthly Fee')
@section('page_title', 'Monthly Fee')

@section('content')

  <div class="card" style="max-width:600px">
    <div class="ch">
      <div class="ct">
        <i class="fas fa-calendar-check"></i>Monthly Membership Fee
      </div>
    </div>

    <form method="POST" action="{{ route('admin.monthly-fee.update') }}">
      @csrf @method('PUT')

      @if ($errors->any())
        <div style="margin:14px 18px 0;padding:10px 12px;
                    background:rgba(239,68,68,0.1);
                    border:1px solid rgba(239,68,68,0.3);
                    border-radius:8px;font-size:12px;color:#f87171;">
          <ul style="margin:0;padding-left:16px;">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <div class="fgrid" style="padding:18px 20px 0;">
        <div class="fg s2">
          <label>Fee Title</label>
          <input type="text" name="title" maxlength="200"
                 value="{{ old('title', $setting->title) }}" required>
        </div>
        <div class="fg">
          <label>Amount (₹, max 7 digits)</label>
          <input type="number" name="amount" min="0.01" max="9999999" step="0.01"
                 value="{{ old('amount', $setting->amount) }}" required>
        </div>
        <div class="fg">
          <label>Due Day of Month</label>
          <input type="number" name="due_day" min="1" max="28"
                 value="{{ old('due_day', $setting->due_day) }}" required>
        </div>
      </div>

      <p style="font-size:11px;color:var(--text-dim);padding:8px 20px 10px;">
        This is charged to every active member automatically, once per month, when they check their dues.
        Changing it only affects dues generated from now on — dues already created for the current month keep their old amount.
      </p>

      <div class="ff">
        <button type="submit" class="btn bp">
          <i class="fas fa-save"></i>Save Changes
        </button>
      </div>
    </form>
  </div>

@endsection