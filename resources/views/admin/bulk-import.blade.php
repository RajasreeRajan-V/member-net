@extends('layouts.admin')
@section('title', 'Bulk Import Members')
@section('page_title', 'Bulk Import Members')

@section('content')

{{-- IMPORT RESULTS --}}
@if(session('import_results'))
  @php $results = session('import_results'); @endphp

  {{-- SUMMARY --}}
  <div style="display:grid;grid-template-columns:repeat(3,1fr);
              gap:12px;margin-bottom:20px;">
    <div class="card" style="margin:0">
      <div style="padding:16px;text-align:center;">
        <div style="font-family:'Instrument Serif',serif;
                    font-size:32px;color:var(--text);">
          {{ $results['total'] }}
        </div>
        <div style="font-size:11px;color:var(--text-dim);">
          Total Rows
        </div>
      </div>
    </div>
    <div class="card" style="margin:0">
      <div style="padding:16px;text-align:center;">
        <div style="font-family:'Instrument Serif',serif;
                    font-size:32px;color:var(--green);">
          {{ $results['created'] }}
        </div>
        <div style="font-size:11px;color:var(--text-dim);">
          ✅ Created Successfully
        </div>
      </div>
    </div>
    <div class="card" style="margin:0">
      <div style="padding:16px;text-align:center;">
        <div style="font-family:'Instrument Serif',serif;
                    font-size:32px;color:var(--red);">
          {{ $results['failed'] }}
        </div>
        <div style="font-size:11px;color:var(--text-dim);">
          ❌ Failed
        </div>
      </div>
    </div>
  </div>

  {{-- SUCCESS LIST --}}
  @if(count($results['success']) > 0)
  <div class="card" style="margin-bottom:18px;">
    <div class="ch">
      <div class="ct">
        <i class="fas fa-check-circle"
           style="color:var(--green)"></i>
        Successfully Created
        ({{ count($results['success']) }})
      </div>
    </div>
    <div class="tw">
      <table>
        <thead>
          <tr>
            <th>Row</th>
            <th>Name</th>
            <th>Email</th>
            <th>Membership ID</th>
            <th>Member Net</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @foreach($results['success'] as $s)
          <tr>
            <td style="color:var(--text-dim)">
              #{{ $s['row'] }}
            </td>
            <td>{{ $s['name'] }}</td>
            <td style="color:var(--text-dim)">
              {{ $s['email'] ?: '—' }}
            </td>
            <td style="font-weight:600;color:var(--accent);">
              {{ $s['membership_id'] ?? '—' }}
            </td>
            <td>
              @if(!empty($s['is_fumma_member']))
                <span class="badge bap">Member Net</span>
              @else
                <span style="color:var(--text-dim);font-size:11px;">Non-Member Net</span>
              @endif
            </td>
            <td>
              <span class="badge bap">Approved</span>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @endif

  {{-- ERROR LIST --}}
  @if(count($results['errors']) > 0)
  <div class="card" style="margin-bottom:18px;">
    <div class="ch">
      <div class="ct">
        <i class="fas fa-times-circle"
           style="color:var(--red)"></i>
        Failed Rows
        ({{ count($results['errors']) }})
      </div>
    </div>
    <div class="tw">
      <table>
        <thead>
          <tr>
            <th>Row</th>
            <th>Name</th>
            <th>Email</th>
            <th>Errors</th>
          </tr>
        </thead>
        <tbody>
          @foreach($results['errors'] as $e)
          <tr>
            <td style="color:var(--text-dim)">
              #{{ $e['row'] }}
            </td>
            <td>{{ $e['name'] }}</td>
            <td style="color:var(--text-dim)">
              {{ $e['email'] ?: '—' }}
            </td>
            <td>
              @foreach($e['errors'] as $err)
              <div style="font-size:11px;color:var(--red);">
                • {{ $err }}
              </div>
              @endforeach
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @endif
@endif

{{-- HOW TO USE --}}
<div class="card" style="margin-bottom:18px;">
  <div class="ch">
    <div class="ct">
      <i class="fas fa-info-circle"></i>
      How to Import Members
    </div>
  </div>
  <div style="padding:20px;">
    <div style="display:grid;grid-template-columns:repeat(3,1fr);
                gap:16px;">
      <div style="text-align:center;padding:16px;">
        <div style="width:44px;height:44px;border-radius:50%;
                    background:rgba(79,142,247,0.1);
                    display:flex;align-items:center;
                    justify-content:center;
                    margin:0 auto 10px;font-size:20px;">
          1️⃣
        </div>
        <div style="font-weight:600;font-size:13px;
                    margin-bottom:6px;">
          Download Template
        </div>
        <div style="font-size:11px;color:var(--text-dim);">
          Download the CSV template and fill member data
        </div>
      </div>
      <div style="text-align:center;padding:16px;">
        <div style="width:44px;height:44px;border-radius:50%;
                    background:rgba(79,142,247,0.1);
                    display:flex;align-items:center;
                    justify-content:center;
                    margin:0 auto 10px;font-size:20px;">
          2️⃣
        </div>
        <div style="font-weight:600;font-size:13px;
                    margin-bottom:6px;">
          Fill Data
        </div>
        <div style="font-size:11px;color:var(--text-dim);">
          Fill member details including shop name and address in Excel or Google Sheets, then export as CSV
        </div>
      </div>
      <div style="text-align:center;padding:16px;">
        <div style="width:44px;height:44px;border-radius:50%;
                    background:rgba(79,142,247,0.1);
                    display:flex;align-items:center;
                    justify-content:center;
                    margin:0 auto 10px;font-size:20px;">
          3️⃣
        </div>
        <div style="font-weight:600;font-size:13px;
                    margin-bottom:6px;">
          Upload & Import
        </div>
        <div style="font-size:11px;color:var(--text-dim);">
          Upload the CSV file and system creates all members
        </div>
      </div>
    </div>

    <div style="margin-top:8px;padding:12px 16px;
                background:rgba(251,146,60,0.06);
                border:1px solid rgba(251,146,60,0.2);
                border-radius:8px;font-size:11.5px;color:var(--orange);">
      <i class="fas fa-triangle-exclamation" style="margin-right:6px;"></i>
      <b>Note:</b> Profile photos cannot be uploaded via CSV.
      Add a photo for each member individually afterward from the Members page (Edit → Photo).
    </div>
  </div>
</div>

{{-- REFERENCE: UNITS --}}
<div class="card" style="margin-bottom:18px;">
  <div class="ch">
    <div class="ct">
      <i class="fas fa-building"></i>
      Units Reference
    </div>
  </div>
  <div class="tw">
    <table>
      <thead>
        <tr>
          <th>Unit ID</th>
          <th>Unit Name</th>
        </tr>
      </thead>
      <tbody id="unitTable">
        @foreach($units as $unit)
        <tr>
          <td><span class="utag">{{ $unit->unit_id ?? str_pad($unit->id, 6, '0', STR_PAD_LEFT) }}</span></td>
          <td style="font-weight:600;">{{ $unit->name }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

{{-- UPLOAD FORM --}}
<div class="card">
  <div class="ch">
    <div class="ct">
      <i class="fas fa-upload"></i>Upload CSV File
    </div>
    <a href="{{ route('admin.bulk-import.template') }}"
       class="btn bo bsm">
      <i class="fas fa-download"></i>Download Template
    </a>
  </div>
  <form method="POST"
        action="{{ route('admin.bulk-import.process') }}"
        enctype="multipart/form-data"
        id="bulkImportForm">
    @csrf
    <div style="padding:24px;">
      {{-- DROP ZONE --}}
      <div id="dropZone"
           style="border:2px dashed var(--border);
                  border-radius:12px;padding:40px;
                  text-align:center;cursor:pointer;
                  transition:all 0.2s;"
           onclick="document.getElementById('csvFile').click()"
           ondragover="event.preventDefault();
                      this.style.borderColor='var(--accent)';
                      this.style.background='rgba(79,142,247,0.05)'"
           ondragleave="this.style.borderColor='var(--border)';
                       this.style.background='transparent'"
           ondrop="handleDrop(event)">
        <div style="font-size:32px;margin-bottom:12px;">📄</div>
        <div style="font-size:14px;font-weight:600;
                    margin-bottom:6px;">
          Click to select or drag & drop CSV file
        </div>
        <div id="fileName" style="font-size:11px;
                                  color:var(--text-dim);">
          Only .csv files accepted · Max 2MB
        </div>
      </div>

      <input type="file" id="csvFile" name="csv_file"
             accept=".csv"
             style="display:none"
             onchange="showFileName(this)">

      {{-- CSV COLUMN GUIDE --}}
      <div style="margin-top:20px;padding:14px 16px;
                  background:rgba(255,255,255,0.02);
                  border:1px solid var(--border);
                  border-radius:8px;font-size:11px;
                  color:var(--text-dim);line-height:2;">
        <div style="font-weight:600;color:var(--text);
                    margin-bottom:8px;">
          📋 CSV Column Order:
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:6px;">
          @foreach(['name', 'email (optional)', 'phone', 'dob (YYYY-MM-DD)', 'shop_name', 'unit', 'address'] as $i => $col)
          <span style="background:rgba(79,142,247,0.1);
                       color:var(--accent-2);
                       padding:2px 8px;border-radius:4px;
                       font-size:10px;font-weight:500;">
            {{ $i+1 }}. {{ $col }}
          </span>
          @endforeach
        </div>
        <div style="margin-top:8px;">
          The template also has a leading <b>sl_no</b> column. It is optional and is skipped automatically on import.
        </div>
        <div style="margin-top:4px;">
          No password column is needed. Each member's <b>phone number</b> is set as their initial password automatically.
        </div>
        <div style="margin-top:4px;">
          <b>Email is optional.</b> Leave the cell empty but keep the comma, e.g. <code>Suresh Kumar,,9845001002</code>.
        </div>
      </div>

      {{-- UNIT MATCHING NOTE --}}
      <div style="margin-top:10px;font-size:11px;color:var(--text-dim);">
        <i class="fas fa-info-circle" style="color:var(--accent)"></i>
        <b>Unit</b> must be entered as either the <b>Unit Name</b> (e.g. <code>{{ $units->first()?->name ?? 'Vadakara' }}</code>) or its <b>Unit ID</b> (e.g. <code>{{ $units->first()?->unit_id ?? 'KAN001' }}</code>) as listed in the reference table above.
      </div>

      {{-- ADDRESS NOTE --}}
      <div style="margin-top:10px;font-size:11px;color:var(--text-dim);">
        <i class="fas fa-info-circle" style="color:var(--accent)"></i>
        <b>Address</b> is required (max 500 characters), e.g. house name, street, area, PIN.
        If you edit the CSV in a plain text editor, wrap addresses that contain commas in double quotes.
        Excel and Google Sheets do this automatically.
      </div>

      <div style="margin-top:16px;display:flex;
                  justify-content:space-between;
                  align-items:center;flex-wrap:wrap;gap:8px;">
        <div style="font-size:11px;color:var(--text-dim);">
          <i class="fas fa-check-circle"
             style="color:var(--green)"></i>
          All imported members will be
          <b style="color:var(--green)">AUTOMATICALLY APPROVED</b> with membership IDs generated.
        </div>
        <button type="submit" class="btn bp"
                id="importBtn">
          <i class="fas fa-upload"></i>Import Members
        </button>
      </div>
    </div>
  </form>
</div>

@endsection

@section('scripts')
<script>
function showFileName(input) {
  const fileName = input.files[0]?.name;
  if (fileName) {
    if (!fileName.toLowerCase().endsWith('.csv')) {
      alert('Please select a .csv file.');
      input.value = '';
      return;
    }
    document.getElementById('fileName').textContent =
      '✅ Selected: ' + fileName;
    document.getElementById('fileName').style.color =
      'var(--green)';
    document.getElementById('dropZone').style.borderColor =
      'var(--accent)';
  }
}

function handleDrop(e) {
  e.preventDefault();
  document.getElementById('dropZone').style.borderColor =
    'var(--border)';
  document.getElementById('dropZone').style.background =
    'transparent';

  const file = e.dataTransfer.files[0];
  if (file && file.name.toLowerCase().endsWith('.csv')) {
    const input = document.getElementById('csvFile');
    const dt    = new DataTransfer();
    dt.items.add(file);
    input.files = dt.files;
    showFileName(input);
  } else {
    alert('Please upload a .csv file!');
  }
}

// Show loading on submit (targets this page's form specifically)
document.getElementById('bulkImportForm').addEventListener('submit',
  function() {
    document.getElementById('importBtn').innerHTML =
      '<i class="fas fa-spinner fa-spin"></i> Importing...';
    document.getElementById('importBtn').disabled = true;
  }
);
</script>
@endsection