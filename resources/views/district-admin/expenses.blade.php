@extends('layouts.district-admin')
@section('title', 'Expenses')
@section('page_title', 'Expenses')

@section('content')

  {{-- ── QUICK STATS ── --}}
  <div class="sg">
    <div class="sc">
      <div class="st">
        <div class="si" style="background:rgba(79,142,247,0.15);color:var(--accent-2);"><i class="fas fa-calendar-day"></i></div>
      </div>
      <div class="sv">₹{{ number_format($todaysExpense, 0) }}</div>
      <div class="sl2">Today's Expense</div>
    </div>
    <div class="sc">
      <div class="st">
        <div class="si" style="background:rgba(52,211,153,0.15);color:var(--green);"><i class="fas fa-calendar-week"></i></div>
      </div>
      <div class="sv">₹{{ number_format($thisMonthExpense, 0) }}</div>
      <div class="sl2">This Month</div>
    </div>
    <div class="sc">
      <div class="st">
        <div class="si" style="background:rgba(245,200,66,0.15);color:var(--gold);"><i class="fas fa-calendar"></i></div>
      </div>
      <div class="sv">₹{{ number_format($thisYearExpense, 0) }}</div>
      <div class="sl2">This Year</div>
    </div>
    <div class="sc">
      <div class="st">
        <div class="si" style="background:rgba(167,139,250,0.15);color:var(--purple);"><i class="fas fa-receipt"></i></div>
      </div>
      <div class="sv">{{ $totalTransactions }}</div>
      <div class="sl2">Transactions (This Year)</div>
    </div>
  </div>

  {{-- ── CATEGORY BREAKDOWN ── --}}
  <div class="card">
    <div class="ch">
      <div class="ct"><i class="fas fa-chart-pie"></i>Category Breakdown — {{ now()->format('F Y') }}</div>
    </div>
    <div class="tw">
      <table>
        <thead>
          <tr><th>Category</th><th>Transactions</th><th>Total</th><th>% of Month</th></tr>
        </thead>
        <tbody>
          @forelse ($categoryTotals as $c)
            <tr>
              <td>{{ $c->category }}</td>
              <td>{{ $c->count }}</td>
              <td>₹{{ number_format($c->total, 2) }}</td>
              <td>{{ $thisMonthExpense > 0 ? number_format(($c->total / $thisMonthExpense) * 100, 1) : 0 }}%</td>
            </tr>
          @empty
            <tr><td colspan="4" style="text-align:center;color:var(--text-dim);">No expenses recorded this month.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- ── MONTHLY REPORT ── --}}
  <div class="card">
    <div class="ch">
      <div class="ct"><i class="fas fa-table"></i>Monthly Report — {{ now()->format('Y') }}</div>
    </div>
    <div class="tw">
      <table>
        <thead>
          <tr><th>Month</th><th>Transactions</th><th>Total</th></tr>
        </thead>
        <tbody>
          @forelse ($monthlyTotals as $m)
            <tr>
              <td>{{ \Carbon\Carbon::create()->month((int) $m->month)->format('F') }}</td>
              <td>{{ $m->count }}</td>
              <td>₹{{ number_format($m->total, 2) }}</td>
            </tr>
          @empty
            <tr><td colspan="3" style="text-align:center;color:var(--text-dim);">No expenses recorded this year.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- ── ALL EXPENSES ── --}}
  <div class="card">
    <div class="ch">
      <div class="ct">
        <i class="fas fa-receipt"></i>All Expenses
      </div>
      <button class="btn bp bsm" onclick="resetAddExpenseModal(); om('addExpenseM')">
        <i class="fas fa-plus"></i>Create Expense
      </button>
    </div>

    <div class="tw">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Title</th>
            <th>Category</th>
            <th>Amount</th>
            <th>Date</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($expenses as $e)
          <tr>
            <td style="color:var(--text-dim)">
              {{ ($expenses->currentPage() - 1) * $expenses->perPage() + $loop->iteration }}
            </td>
            <td>
              <div>
                <div class="mn">{{ $e->title }}</div>
                <div class="me">{{ $e->paid_to ?? '—' }}</div>
              </div>
            </td>
            <td style="color:var(--text-dim)">{{ $e->category }}</td>
            <td style="color:var(--text-dim)">₹{{ number_format($e->amount, 2) }}</td>
            <td style="color:var(--text-dim)">{{ $e->expense_date->format('d M Y') }}</td>
            <td>
              <span class="badge {{ $e->status == 'PAID' ? 'bap' : ($e->status == 'REJECTED' ? 'brj' : ($e->status == 'APPROVED' ? 'ban' : 'bpe')) }}">
                {{ $e->status }}
              </span>
            </td>
            <td>
              <div class="abr">
                {{-- VIEW --}}
                <div class="ab vw"
                     onclick="viewExpense(
                       '{{ addslashes($e->title) }}',
                       '{{ addslashes($e->category) }}',
                       '{{ $e->amount }}',
                       '{{ $e->expense_date->format('d M Y') }}',
                       '{{ $e->expense_time ? \Carbon\Carbon::parse($e->expense_time)->format('h:i A') : '—' }}',
                       '{{ addslashes($e->paid_to ?? '—') }}',
                       '{{ $e->status }}')"
                     title="View">
                  <i class="fas fa-eye"></i>
                </div>

                {{-- EDIT --}}
                <div class="ab ed"
                     onclick="editExpense(
                       {{ $e->id }},
                       '{{ addslashes($e->title) }}',
                       '{{ $e->category }}',
                       '{{ $e->amount }}',
                       '{{ $e->expense_date->format('Y-m-d') }}',
                       '{{ $e->expense_time ? \Carbon\Carbon::parse($e->expense_time)->format('H:i') : '' }}',
                       '{{ addslashes($e->paid_to ?? '') }}',
                       '{{ $e->status }}')"
                     title="Edit">
                  <i class="fas fa-edit"></i>
                </div>

                {{-- DELETE --}}
                <form method="POST" action="{{ route('district-admin.expenses.delete') }}" style="display:inline"
                      onsubmit="return confirm('Delete {{ addslashes($e->title) }}?')">
                  @csrf @method('DELETE')
                  <input type="hidden" name="id" value="{{ $e->id }}">
                  <button type="submit" class="ab rej" title="Delete">
                    <i class="fas fa-trash"></i>
                  </button>
                </form>
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="7" style="text-align:center;color:var(--text-dim);padding:40px;">
              <div style="font-size:32px;margin-bottom:10px;">🧾</div>
              <div style="font-size:13px;font-weight:600;margin-bottom:6px;">No expenses yet</div>
              <div style="font-size:11px;color:var(--text-dim);margin-bottom:14px;">
                Record your first expense
              </div>
              <button class="btn bp bsm" onclick="resetAddExpenseModal(); om('addExpenseM')">
                <i class="fas fa-plus"></i>Create Expense
              </button>
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- ── PAGINATION ── --}}
    @if($expenses->hasPages())
      <div style="padding:12px 16px;border-top:1px solid var(--border);
                  display:flex;align-items:center;justify-content:space-between;
                  flex-wrap:wrap;gap:8px;">
        <span style="font-size:11px;color:var(--text-dim);">
          Showing {{ $expenses->firstItem() }}–{{ $expenses->lastItem() }}
          of {{ $expenses->total() }} items
        </span>
        {{ $expenses->appends(request()->query())->links('pagination.custom') }}
      </div>
    @endif

  </div>

{{-- ══ ADD EXPENSE MODAL ══ --}}
<div id="addExpenseM" class="mo">
  <div class="md" style="width:560px">
    <div class="mh">
      <span class="mt2">Create Expense</span>
      <div class="mc3" onclick="resetAddExpenseModal(); cm('addExpenseM')">
        <i class="fas fa-times"></i>
      </div>
    </div>
    <form method="POST" action="{{ route('district-admin.expenses.store') }}" id="addExpenseForm">
      @csrf

      @if ($errors->any())
        <div id="addExpenseError" style="margin:14px 18px 0;padding:10px 12px;
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

      <div class="fgrid">
        <div class="fg s2">
          <label>Title</label>
          <input type="text" name="title" maxlength="200"
                 value="{{ old('title') }}" placeholder="e.g. Hall Booking" required>
        </div>
        <div class="fg">
          <label>Category</label>
          <select name="category" required>
            @foreach ($categories as $cat)
              <option value="{{ $cat }}" {{ old('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
            @endforeach
          </select>
        </div>
        <div class="fg">
          <label>Amount (₹)</label>
          <input type="number" name="amount" min="0.01" max="9999999" step="0.01"
                 value="{{ old('amount') }}" required>
        </div>
        <div class="fg">
          <label>Date</label>
          <input type="date" name="expense_date" value="{{ old('expense_date') }}" required>
        </div>
        <div class="fg">
          <label>Time</label>
          <input type="time" name="expense_time" value="{{ old('expense_time') }}">
        </div>
        <div class="fg s2">
          <label>Paid To</label>
          <input type="text" name="paid_to" maxlength="150" value="{{ old('paid_to') }}">
        </div>
        <div class="fg s2">
          <label>Status</label>
          <select name="status" required>
            <option value="PAID">PAID</option>
            <option value="APPROVED">APPROVED</option>
            <option value="PENDING">PENDING</option>
            <option value="REJECTED">REJECTED</option>
          </select>
        </div>
      </div>
      <div class="ff">
        <button type="button" class="btn bo" onclick="resetAddExpenseModal(); cm('addExpenseM')">Cancel</button>
        <button type="submit" class="btn bp">
          <i class="fas fa-save"></i>Save Expense
        </button>
      </div>
    </form>
  </div>
</div>

{{-- ══ EDIT EXPENSE MODAL ══ --}}
<div id="editExpenseM" class="mo">
  <div class="md" style="width:560px">
    <div class="mh">
      <span class="mt2">Edit Expense</span>
      <div class="mc3" onclick="resetEditExpenseModal(); cm('editExpenseM')">
        <i class="fas fa-times"></i>
      </div>
    </div>
    <form method="POST" action="{{ route('district-admin.expenses.update') }}" id="editExpenseForm">
  @csrf
  <input type="hidden" name="id" id="editExpenseId">
      <div class="fgrid">
        <div class="fg s2">
          <label>Title</label>
          <input type="text" name="title" id="editExpenseTitle" maxlength="200" required>
        </div>
        <div class="fg">
          <label>Category</label>
          <select name="category" id="editExpenseCategory" required>
            @foreach ($categories as $cat)
              <option value="{{ $cat }}">{{ $cat }}</option>
            @endforeach
          </select>
        </div>
        <div class="fg">
          <label>Amount (₹)</label>
          <input type="number" name="amount" id="editExpenseAmount" min="0.01" max="9999999" step="0.01" required>
        </div>
        <div class="fg">
          <label>Date</label>
          <input type="date" name="expense_date" id="editExpenseDate" required>
        </div>
        <div class="fg">
          <label>Time</label>
          <input type="time" name="expense_time" id="editExpenseTime">
        </div>
        <div class="fg s2">
          <label>Paid To</label>
          <input type="text" name="paid_to" id="editExpensePaidTo" maxlength="150">
        </div>
        <div class="fg s2">
          <label>Status</label>
          <select name="status" id="editExpenseStatus" required>
            <option value="PAID">PAID</option>
            <option value="APPROVED">APPROVED</option>
            <option value="PENDING">PENDING</option>
            <option value="REJECTED">REJECTED</option>
          </select>
        </div>
      </div>
      <div class="ff">
        <button type="button" class="btn bo" onclick="resetEditExpenseModal(); cm('editExpenseM')">Cancel</button>
        <button type="submit" class="btn bp">
          <i class="fas fa-save"></i>Update Expense
        </button>
      </div>
    </form>
  </div>
</div>

{{-- ══ VIEW EXPENSE MODAL ══ --}}
<div id="viewExpenseM" class="mo">
  <div class="md" style="width:480px">
    <div class="mh">
      <span class="mt2" id="viewExpenseTitle">Expense Detail</span>
      <div class="mc3" onclick="cm('viewExpenseM')">
        <i class="fas fa-times"></i>
      </div>
    </div>
    <div style="padding:20px;">
      <div style="display:flex;gap:8px;margin-bottom:14px;align-items:center;flex-wrap:wrap;">
        <span id="viewExpenseStatus" class="badge"></span>
        <span id="viewExpenseCategory" style="font-size:11px;color:var(--text-dim);"></span>
      </div>
      <div style="font-size:13px;line-height:2;color:var(--text);">
        <div><strong>Amount:</strong> <span id="viewExpenseAmount"></span></div>
        <div><strong>Date:</strong> <span id="viewExpenseDate"></span></div>
        <div><strong>Time:</strong> <span id="viewExpenseTime"></span></div>
        <div><strong>Paid To:</strong> <span id="viewExpensePaidTo"></span></div>
      </div>
    </div>
    <div class="ff">
      <button class="btn bo" onclick="cm('viewExpenseM')">Close</button>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script>
function resetAddExpenseModal() {
  const errBox = document.getElementById('addExpenseError');
  if (errBox) errBox.remove();

  const form = document.getElementById('addExpenseForm');
  if (form) form.reset();
}

function resetEditExpenseModal() {
  const form = document.getElementById('editExpenseForm');
  if (form) form.reset();

  document.getElementById('editExpenseId').value = '';
}

function editExpense(id, title, category, amount, date, time, paidTo, status) {
  resetEditExpenseModal();
  document.getElementById('editExpenseId').value       = id;
  document.getElementById('editExpenseTitle').value    = title;
  document.getElementById('editExpenseCategory').value = category;
  document.getElementById('editExpenseAmount').value   = amount;
  document.getElementById('editExpenseDate').value     = date;
  document.getElementById('editExpenseTime').value     = time;
  document.getElementById('editExpensePaidTo').value   = paidTo;
  document.getElementById('editExpenseStatus').value   = status;
  om('editExpenseM');
}

function viewExpense(title, category, amount, date, time, paidTo, status) {
  document.getElementById('viewExpenseTitle').textContent    = title;
  document.getElementById('viewExpenseCategory').textContent = category;
  document.getElementById('viewExpenseAmount').textContent   = '₹' + parseFloat(amount).toFixed(2);
  document.getElementById('viewExpenseDate').textContent     = date;
  document.getElementById('viewExpenseTime').textContent     = time;
  document.getElementById('viewExpensePaidTo').textContent   = paidTo;

  const statusEl = document.getElementById('viewExpenseStatus');
  statusEl.textContent = status;
  statusEl.className   = 'badge ' + (
    status === 'PAID' ? 'bap' :
    status === 'REJECTED' ? 'brj' :
    status === 'APPROVED' ? 'ban' : 'bpe'
  );

  om('viewExpenseM');
}

@if ($errors->any())
  document.addEventListener('DOMContentLoaded', function () {
    om('addExpenseM');
  });
@endif
</script>
@endsection