@extends('unit-admin.layout')
@section('title', 'Certificate Settings')
@section('page_title', 'Certificate Settings')

@section('content')

@if($errors->any())
  <div style="margin-bottom:16px;padding:12px 16px;border-radius:8px;background:rgba(239,68,68,0.1);border:1px solid var(--red,#ef4444);color:var(--red,#ef4444);font-size:13px;">
    <div style="font-weight:700;margin-bottom:4px;">Please fix the following:</div>
    <ul style="margin:0 0 0 16px;padding:0;">
      @foreach($errors->all() as $err)
        <li>{{ $err }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div style="display:grid;grid-template-columns:1.2fr 0.8fr;gap:20px;align-items:start;">

  {{-- LEFT: CONFIGURATION FORM --}}
  <div class="card" style="margin-bottom:0;">
    <div class="ch">
      <div class="ct">
        <i class="fas fa-certificate" style="color:var(--accent);"></i>Unit Certificate Configuration
      </div>
      <a href="{{ route('unit-admin.certificate.preview') }}" target="_blank" class="btn bp bsm">
        <i class="fas fa-eye"></i>Preview Sample PDF
      </a>
    </div>

    <form method="POST" action="{{ route('unit-admin.certificate.update') }}" enctype="multipart/form-data" style="padding:20px;">
      @csrf

      <div style="font-size:12px;color:var(--text-dim);margin-bottom:18px;line-height:1.5;">
        Configure the official certificate logo and details for <b>{{ $unit->name }} Unit</b>. All membership certificates generated for members of your unit will automatically use this branding.
      </div>

      {{-- LOGO UPLOAD SECTION --}}
      <div style="background:var(--card);border:1px solid var(--border);border-radius:10px;padding:16px;margin-bottom:18px;">
        <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-dim);display:block;margin-bottom:10px;">
          Certificate Logo
        </label>

        <div style="display:flex;align-items:center;gap:18px;flex-wrap:wrap;">
          <div style="width:110px;height:80px;border-radius:8px;background:#ffffff;border:1px solid var(--border);display:flex;align-items:center;justify-content:center;padding:6px;box-shadow:0 2px 6px rgba(0,0,0,0.06);flex-shrink:0;">
            @if($unit->certificate_logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($unit->certificate_logo))
              <img id="logoPreviewImg" src="{{ asset('storage/' . $unit->certificate_logo) }}" style="max-width:100%;max-height:100%;object-fit:contain;">
            @else
              <img id="logoPreviewImg" src="{{ asset('fumma-logo.png') }}" style="max-width:100%;max-height:100%;object-fit:contain;">
            @endif
          </div>

          <div style="flex:1;min-width:180px;">
            <div style="font-size:12px;font-weight:600;color:var(--text);margin-bottom:4px;">
              @if($unit->certificate_logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($unit->certificate_logo))
                <span class="badge bac" style="font-size:10px;padding:2px 7px;">Custom Unit Logo</span>
              @else
                <span class="badge" style="background:rgba(255,255,255,0.08);color:var(--text-dim);font-size:10px;padding:2px 7px;">Default Central Logo</span>
              @endif
            </div>

            <input type="file" name="certificate_logo" id="certLogoInput" accept="image/png,image/jpeg,image/webp"
                   onchange="previewLogo(this)"
                   style="font-size:11px;color:var(--text);margin-top:4px;">
            <div style="font-size:10.5px;color:var(--text-dim);margin-top:4px;">
              PNG with transparent background recommended (Max 3MB).
            </div>
          </div>
        </div>

        @if($unit->certificate_logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($unit->certificate_logo))
          <div style="margin-top:12px;padding-top:10px;border-top:1px solid var(--border);text-align:right;">
            <button type="button" class="btn bo bsm" style="color:var(--red);border-color:rgba(239,68,68,0.3);"
                    onclick="if(confirm('Remove unit logo and revert to the default central logo?')) document.getElementById('removeLogoForm').submit();">
              <i class="fas fa-trash-alt"></i>Remove Logo (Reset to Default)
            </button>
          </div>
        @endif
      </div>

      {{-- SEAL TEXT --}}
      <div class="fg" style="margin-bottom:14px;">
        <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-dim);display:block;margin-bottom:6px;">
          Certificate Seal Text (Optional)
        </label>
        <input type="text" name="certificate_seal_text" value="{{ old('certificate_seal_text', $unit->certificate_seal_text) }}"
               placeholder="e.g. {{ $unit->name }} 2026 (leave empty for default '{{ $unit->name }} 2026')"
               maxlength="100"
               style="width:100%;padding:8px 12px;font-size:12.5px;border-radius:8px;border:1px solid var(--border);background:var(--card);color:var(--text);">
        <span style="font-size:10.5px;color:var(--text-dim);margin-top:3px;display:block;">
          Appears in the gold circular seal at the bottom of the certificate.
        </span>
      </div>

      {{-- UNIT SIGNATORIES --}}
      <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-dim);margin:22px 0 10px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:6px;">
        <span style="display:flex;align-items:center;gap:6px;">
          <i class="fas fa-file-signature" style="color:var(--accent);"></i>Certificate Signatories
        </span>
        <span style="font-size:10.5px;font-weight:500;text-transform:none;color:var(--text-dim);">
          Customize each person's name, designation, and signature
        </span>
      </div>

      {{-- SIGNATORY 1: PRESIDENT --}}
      <div style="background:rgba(255,255,255,0.02);border:1px solid var(--border);border-radius:10px;padding:16px;margin-bottom:14px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
          <div style="font-size:12px;font-weight:700;color:var(--text);display:flex;align-items:center;gap:6px;">
            <span style="background:rgba(79,142,247,0.15);color:var(--accent);width:20px;height:20px;border-radius:4px;display:inline-flex;align-items:center;justify-content:center;font-size:11px;">1</span>
            Signatory 1 (President)
          </div>
          @if($unit->signatory_1_signature && \Illuminate\Support\Facades\Storage::disk('public')->exists($unit->signatory_1_signature))
            <button type="button" class="btn bo bsm" style="font-size:10.5px;padding:2px 8px;color:var(--red);border-color:rgba(239,68,68,0.3);"
                    onclick="if(confirm('Remove custom signature and revert to default?')) document.getElementById('removeSig1Form').submit();">
              <i class="fas fa-trash-alt"></i>Remove Signature
            </button>
          @endif
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
          <div class="fg">
            <label style="font-size:11px;color:var(--text-dim);display:block;margin-bottom:4px;">Full Name</label>
            <input type="text" name="signatory_1_name" value="{{ old('signatory_1_name', $unit->signatory_1_name ?? $unit->certificate_president_name) }}"
                   placeholder="e.g. Tony Pulickal (or Unit President Name)"
                   maxlength="100"
                   style="width:100%;padding:8px 12px;font-size:12px;border-radius:8px;border:1px solid var(--border);background:var(--card);color:var(--text);">
          </div>
          <div class="fg">
            <label style="font-size:11px;color:var(--text-dim);display:block;margin-bottom:4px;">Designation</label>
            <input type="text" name="signatory_1_title" value="{{ old('signatory_1_title', $unit->signatory_1_title) }}"
                   placeholder="e.g. {{ $unit->name }} Unit President (or State President)"
                   maxlength="100"
                   style="width:100%;padding:8px 12px;font-size:12px;border-radius:8px;border:1px solid var(--border);background:var(--card);color:var(--text);">
          </div>
        </div>

        <div>
          <label style="font-size:11px;color:var(--text-dim);display:block;margin-bottom:4px;">Signature Image (PNG / Transparent recommended)</label>
          <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
            <div style="width:140px;height:45px;border-radius:6px;background:#ffffff;border:1px solid var(--border);display:flex;align-items:center;justify-content:center;padding:4px;box-shadow:0 1px 4px rgba(0,0,0,0.05);flex-shrink:0;">
              @if($unit->signatory_1_signature && \Illuminate\Support\Facades\Storage::disk('public')->exists($unit->signatory_1_signature))
                <img id="sig1Preview" src="{{ asset('storage/' . $unit->signatory_1_signature) }}" style="max-height:100%;max-width:100%;object-fit:contain;">
              @else
                <img id="sig1Preview" src="{{ asset('sign-president.png') }}" style="max-height:100%;max-width:100%;object-fit:contain;" title="Default signature">
              @endif
            </div>
            <div style="flex:1;min-width:180px;">
              <input type="file" name="signatory_1_signature" accept="image/png,image/jpeg,image/webp"
                     onchange="previewSignature(this, 'sig1Preview')"
                     style="font-size:11px;color:var(--text);">
              <div style="font-size:10px;color:var(--text-dim);margin-top:2px;">
                @if($unit->signatory_1_signature)
                  <span style="color:var(--green);"><i class="fas fa-check-circle"></i> Custom signature active</span>
                @else
                  <span>Default signature active (Upload PNG to replace)</span>
                @endif
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- SIGNATORY 2: GENERAL SECRETARY --}}
      <div style="background:rgba(255,255,255,0.02);border:1px solid var(--border);border-radius:10px;padding:16px;margin-bottom:14px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
          <div style="font-size:12px;font-weight:700;color:var(--text);display:flex;align-items:center;gap:6px;">
            <span style="background:rgba(79,142,247,0.15);color:var(--accent);width:20px;height:20px;border-radius:4px;display:inline-flex;align-items:center;justify-content:center;font-size:11px;">2</span>
            Signatory 2 (General Secretary)
          </div>
          @if($unit->signatory_2_signature && \Illuminate\Support\Facades\Storage::disk('public')->exists($unit->signatory_2_signature))
            <button type="button" class="btn bo bsm" style="font-size:10.5px;padding:2px 8px;color:var(--red);border-color:rgba(239,68,68,0.3);"
                    onclick="if(confirm('Remove custom signature and revert to default?')) document.getElementById('removeSig2Form').submit();">
              <i class="fas fa-trash-alt"></i>Remove Signature
            </button>
          @endif
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
          <div class="fg">
            <label style="font-size:11px;color:var(--text-dim);display:block;margin-bottom:4px;">Full Name</label>
            <input type="text" name="signatory_2_name" value="{{ old('signatory_2_name', $unit->signatory_2_name ?? $unit->certificate_secretary_name) }}"
                   placeholder="e.g. Shaji Manhar (or Unit Secretary Name)"
                   maxlength="100"
                   style="width:100%;padding:8px 12px;font-size:12px;border-radius:8px;border:1px solid var(--border);background:var(--card);color:var(--text);">
          </div>
          <div class="fg">
            <label style="font-size:11px;color:var(--text-dim);display:block;margin-bottom:4px;">Designation</label>
            <input type="text" name="signatory_2_title" value="{{ old('signatory_2_title', $unit->signatory_2_title) }}"
                   placeholder="e.g. {{ $unit->name }} Unit Gen. Secretary (or State General Secretary)"
                   maxlength="100"
                   style="width:100%;padding:8px 12px;font-size:12px;border-radius:8px;border:1px solid var(--border);background:var(--card);color:var(--text);">
          </div>
        </div>

        <div>
          <label style="font-size:11px;color:var(--text-dim);display:block;margin-bottom:4px;">Signature Image (PNG / Transparent recommended)</label>
          <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
            <div style="width:140px;height:45px;border-radius:6px;background:#ffffff;border:1px solid var(--border);display:flex;align-items:center;justify-content:center;padding:4px;box-shadow:0 1px 4px rgba(0,0,0,0.05);flex-shrink:0;">
              @if($unit->signatory_2_signature && \Illuminate\Support\Facades\Storage::disk('public')->exists($unit->signatory_2_signature))
                <img id="sig2Preview" src="{{ asset('storage/' . $unit->signatory_2_signature) }}" style="max-height:100%;max-width:100%;object-fit:contain;">
              @else
                <img id="sig2Preview" src="{{ asset('sign-secretary.png') }}" style="max-height:100%;max-width:100%;object-fit:contain;" title="Default signature">
              @endif
            </div>
            <div style="flex:1;min-width:180px;">
              <input type="file" name="signatory_2_signature" accept="image/png,image/jpeg,image/webp"
                     onchange="previewSignature(this, 'sig2Preview')"
                     style="font-size:11px;color:var(--text);">
              <div style="font-size:10px;color:var(--text-dim);margin-top:2px;">
                @if($unit->signatory_2_signature)
                  <span style="color:var(--green);"><i class="fas fa-check-circle"></i> Custom signature active</span>
                @else
                  <span>Default signature active (Upload PNG to replace)</span>
                @endif
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- SIGNATORY 3: TREASURER --}}
      <div style="background:rgba(255,255,255,0.02);border:1px solid var(--border);border-radius:10px;padding:16px;margin-bottom:18px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
          <div style="font-size:12px;font-weight:700;color:var(--text);display:flex;align-items:center;gap:6px;">
            <span style="background:rgba(79,142,247,0.15);color:var(--accent);width:20px;height:20px;border-radius:4px;display:inline-flex;align-items:center;justify-content:center;font-size:11px;">3</span>
            Signatory 3 (Treasurer)
          </div>
          @if($unit->signatory_3_signature && \Illuminate\Support\Facades\Storage::disk('public')->exists($unit->signatory_3_signature))
            <button type="button" class="btn bo bsm" style="font-size:10.5px;padding:2px 8px;color:var(--red);border-color:rgba(239,68,68,0.3);"
                    onclick="if(confirm('Remove custom signature and revert to default?')) document.getElementById('removeSig3Form').submit();">
              <i class="fas fa-trash-alt"></i>Remove Signature
            </button>
          @endif
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
          <div class="fg">
            <label style="font-size:11px;color:var(--text-dim);display:block;margin-bottom:4px;">Full Name</label>
            <input type="text" name="signatory_3_name" value="{{ old('signatory_3_name', $unit->signatory_3_name) }}"
                   placeholder="e.g. Rafi Puthoor (or Unit Treasurer Name)"
                   maxlength="100"
                   style="width:100%;padding:8px 12px;font-size:12px;border-radius:8px;border:1px solid var(--border);background:var(--card);color:var(--text);">
          </div>
          <div class="fg">
            <label style="font-size:11px;color:var(--text-dim);display:block;margin-bottom:4px;">Designation</label>
            <input type="text" name="signatory_3_title" value="{{ old('signatory_3_title', $unit->signatory_3_title) }}"
                   placeholder="e.g. {{ $unit->name }} Unit Treasurer (or State Treasurer)"
                   maxlength="100"
                   style="width:100%;padding:8px 12px;font-size:12px;border-radius:8px;border:1px solid var(--border);background:var(--card);color:var(--text);">
          </div>
        </div>

        <div>
          <label style="font-size:11px;color:var(--text-dim);display:block;margin-bottom:4px;">Signature Image (PNG / Transparent recommended)</label>
          <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
            <div style="width:140px;height:45px;border-radius:6px;background:#ffffff;border:1px solid var(--border);display:flex;align-items:center;justify-content:center;padding:4px;box-shadow:0 1px 4px rgba(0,0,0,0.05);flex-shrink:0;">
              @if($unit->signatory_3_signature && \Illuminate\Support\Facades\Storage::disk('public')->exists($unit->signatory_3_signature))
                <img id="sig3Preview" src="{{ asset('storage/' . $unit->signatory_3_signature) }}" style="max-height:100%;max-width:100%;object-fit:contain;">
              @else
                <img id="sig3Preview" src="{{ asset('sign-treasurer.png') }}" style="max-height:100%;max-width:100%;object-fit:contain;" title="Default signature">
              @endif
            </div>
            <div style="flex:1;min-width:180px;">
              <input type="file" name="signatory_3_signature" accept="image/png,image/jpeg,image/webp"
                     onchange="previewSignature(this, 'sig3Preview')"
                     style="font-size:11px;color:var(--text);">
              <div style="font-size:10px;color:var(--text-dim);margin-top:2px;">
                @if($unit->signatory_3_signature)
                  <span style="color:var(--green);"><i class="fas fa-check-circle"></i> Custom signature active</span>
                @else
                  <span>Default signature active (Upload PNG to replace)</span>
                @endif
              </div>
            </div>
          </div>
        </div>
      </div>

      <div style="display:flex;justify-content:flex-end;gap:10px;padding-top:12px;border-top:1px solid var(--border);">
        <button type="submit" class="btn bp">
          <i class="fas fa-save"></i>Save Certificate Settings
        </button>
      </div>
    </form>

    {{-- HIDDEN FORM TO DELETE LOGO --}}
    <form id="removeLogoForm" method="POST" action="{{ route('unit-admin.certificate.remove-logo') }}" style="display:none;">
      @csrf
      @method('DELETE')
    </form>

    {{-- HIDDEN FORMS TO DELETE SIGNATURES --}}
    <form id="removeSig1Form" method="POST" action="{{ route('unit-admin.certificate.remove-signature', 1) }}" style="display:none;">
      @csrf
      @method('DELETE')
    </form>
    <form id="removeSig2Form" method="POST" action="{{ route('unit-admin.certificate.remove-signature', 2) }}" style="display:none;">
      @csrf
      @method('DELETE')
    </form>
    <form id="removeSig3Form" method="POST" action="{{ route('unit-admin.certificate.remove-signature', 3) }}" style="display:none;">
      @csrf
      @method('DELETE')
    </form>
  </div>

  {{-- RIGHT: PREVIEW & INFO CARD --}}
  <div style="display:flex;flex-direction:column;gap:16px;">
    <div class="card" style="margin-bottom:0;">
      <div class="ch">
        <div class="ct">
          <i class="fas fa-info-circle" style="color:var(--accent);"></i>How Unit Certificates Work
        </div>
      </div>
      <div style="padding:16px;font-size:12px;color:var(--text-dim);line-height:1.6;">
        <div style="display:flex;gap:10px;margin-bottom:12px;">
          <div style="width:24px;height:24px;border-radius:50%;background:rgba(79,142,247,0.15);color:var(--accent);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:11px;flex-shrink:0;">1</div>
          <div><b>Unique Logo:</b> Your uploaded logo is rendered on your members' certificates and returned via the Member Certificate API.</div>
        </div>
        <div style="display:flex;gap:10px;margin-bottom:12px;">
          <div style="width:24px;height:24px;border-radius:50%;background:rgba(79,142,247,0.15);color:var(--accent);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:11px;flex-shrink:0;">2</div>
          <div><b>Instant Updates:</b> When you update your logo or details, new downloads by approved members immediately reflect the changes.</div>
        </div>
        <div style="display:flex;gap:10px;">
          <div style="width:24px;height:24px;border-radius:50%;background:rgba(79,142,247,0.15);color:var(--accent);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:11px;flex-shrink:0;">3</div>
          <div><b>Independent Isolation:</b> Each unit's branding is completely isolated; changes here never affect other units.</div>
        </div>
      </div>
    </div>

    <div class="card" style="margin-bottom:0;text-align:center;padding:20px;">
      <i class="fas fa-file-pdf" style="font-size:36px;color:var(--accent);margin-bottom:10px;display:block;"></i>
      <div style="font-weight:700;font-size:14px;color:var(--text);margin-bottom:4px;">Test Certificate Output</div>
      <div style="font-size:11.5px;color:var(--text-dim);margin-bottom:16px;">
        Generate and inspect a sample landscape PDF certificate using {{ $unit->name }}'s current settings.
      </div>
      <a href="{{ route('unit-admin.certificate.preview') }}" target="_blank" class="btn bp" style="display:inline-flex;">
        <i class="fas fa-external-link-alt"></i>Open Sample Certificate PDF
      </a>
    </div>
  </div>

</div>

@endsection

@section('scripts')
<script>
function previewLogo(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById('logoPreviewImg').src = e.target.result;
    };
    reader.readAsDataURL(input.files[0]);
  }
}

function previewSignature(input, previewId) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById(previewId).src = e.target.result;
    };
    reader.readAsDataURL(input.files[0]);
  }
}
</script>
@endsection
