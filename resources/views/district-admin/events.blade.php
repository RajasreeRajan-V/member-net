@extends('layouts.district-admin')
@section('title', 'Events')
@section('page_title', 'Events')

@section('content')

<div class="card">
  <div class="ch">
    <div class="ct"><i class="fas fa-calendar-alt"></i> Events</div>
    <button class="btn bp bsm" onclick="resetAddEventModal(); om('addEventM')">
      <i class="fas fa-plus"></i> Create Event
    </button>
  </div>

  <div class="tw">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Image</th>
          <th>Event</th>
          <th>Date & Time</th>
          <th>Seats</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($events as $event)
        <tr>
          <td style="color:var(--text-dim)">{{ $loop->iteration }}</td>

          {{-- IMAGE --}}
          <td>
            @if($event->image)
              <img src="{{ asset('storage/' . $event->image) }}" alt="Event Image"
                   style="width:50px;height:50px;object-fit:cover;border-radius:6px;">
            @else
              <span style="font-size:10px;color:var(--text-dim)">No Image</span>
            @endif
          </td>

          {{-- EVENT --}}
          <td>
            <div class="mn">{{ $event->title }}</div>
            <div class="me">📍 {{ $event->location ?? '—' }}</div>
          </td>

          {{-- DATE & TIME --}}
          <td style="color:var(--text-dim)">
            <div>{{ $event->event_date->format('d M Y') }}</div>
            <div style="font-size:10px;">{{ $event->event_time }}</div>
          </td>

          {{-- SEATS --}}
          <td style="color:var(--text-dim)">
            <div>{{ $event->registrations_count }} registered</div>
            <div style="font-size:10px;">
              {{ max(0, $event->total_seats - $event->registrations_count) }} left
            </div>
            <span class="badge bap" style="margin-top:3px;">FREE</span>
          </td>

          {{-- STATUS --}}
          <td>
            <select onchange="updateStatus({{ $event->id }}, this.value)"
                    style="padding:3px 7px;font-size:10px;border-radius:20px;width:auto;
                           background:transparent;border:1px solid var(--border);
                           color:
                           @if($event->status == 'UPCOMING') var(--accent)
                           @elseif($event->status == 'ONGOING') var(--green)
                           @elseif($event->status == 'COMPLETED') var(--text-dim)
                           @else var(--red) @endif;
                           cursor:pointer;">
              <option value="UPCOMING"  {{ $event->status == 'UPCOMING'  ? 'selected' : '' }}>UPCOMING</option>
              <option value="ONGOING"   {{ $event->status == 'ONGOING'   ? 'selected' : '' }}>ONGOING</option>
              <option value="COMPLETED" {{ $event->status == 'COMPLETED' ? 'selected' : '' }}>COMPLETED</option>
              <option value="CANCELLED" {{ $event->status == 'CANCELLED' ? 'selected' : '' }}>CANCELLED</option>
            </select>
            <form id="statusForm{{ $event->id }}" method="POST"
                  action="{{ route('district-admin.events.status') }}" style="display:none">
              @csrf
              <input type="hidden" name="id" value="{{ $event->id }}">
              <input type="hidden" name="status" id="statusVal{{ $event->id }}">
            </form>
          </td>

          {{-- ACTIONS --}}
          <td>
            <div class="abr">
              <a href="{{ route('district-admin.events.registrations', $event->id) }}"
                 class="ab vw" title="Registrations">
                <i class="fas fa-users"></i>
              </a>
              <div class="ab ed" onclick="editEvent({{ $event->id }})" title="Edit">
                <i class="fas fa-edit"></i>
              </div>
              <form method="POST" action="{{ route('district-admin.events.delete') }}"
                    style="display:inline"
                    onsubmit="return confirm('Delete this event?')">
                @csrf @method('DELETE')
                <input type="hidden" name="id" value="{{ $event->id }}">
                <button type="submit" class="ab rej" title="Delete"><i class="fas fa-trash"></i></button>
              </form>
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="7" style="text-align:center;color:var(--text-dim);padding:40px;">
            <div style="font-size:32px;margin-bottom:10px;">📅</div>
            <div style="font-size:13px;font-weight:600;margin-bottom:6px;">No events yet</div>
            <div style="font-size:11px;margin-bottom:14px;">Create your first event</div>
            <button class="btn bp bsm" onclick="resetAddEventModal(); om('addEventM')">
              <i class="fas fa-plus"></i> Create Event
            </button>
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($events->hasPages())
    <div style="padding:12px 16px;border-top:1px solid var(--border);">
      {{ $events->links('pagination.custom') }}
    </div>
  @endif
</div>

{{-- ══ ADD EVENT MODAL ══ --}}
<div id="addEventM" class="mo">
  <div class="md" style="width:600px">
    <div class="mh">
      <span class="mt2">Create New Event</span>
      <div class="mc3" onclick="resetAddEventModal(); cm('addEventM')"><i class="fas fa-times"></i></div>
    </div>
    <form method="POST" action="{{ route('district-admin.events.store') }}" enctype="multipart/form-data" id="addEventForm">
      @csrf
      <input type="hidden" name="form" value="create">

      @if ($errors->any() && old('form') === 'create')
        <div id="addEventError" style="margin:14px 18px 0;padding:10px 12px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:8px;font-size:12px;color:#f87171;">
          <ul style="margin:0;padding-left:16px;">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
          </ul>
        </div>
      @endif

      <div style="padding:14px 18px 0;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--accent);">
        Event Info
      </div>
      <div class="fgrid">
        <div class="fg s2">
          <label>Event Title</label>
          <input type="text" name="title" maxlength="200" placeholder="Enter event title"
                 value="{{ old('form') === 'create' ? old('title') : '' }}" required>
        </div>
        <div class="fg s2">
          <label>Description</label>
          <textarea name="description" placeholder="Describe the event..." style="min-height:80px" required>{{ old('form') === 'create' ? old('description') : '' }}</textarea>
        </div>
        <div class="fg">
          <label>Event Date</label>
          <input type="date" name="event_date" id="addEventDate" min="{{ now()->format('Y-m-d') }}"
                 value="{{ old('form') === 'create' ? old('event_date') : '' }}" required>
        </div>
        <div class="fg">
          <label>Event Time</label>
          <input type="time" name="event_time" value="{{ old('form') === 'create' ? old('event_time') : '' }}" required>
        </div>
        <div class="fg s2">
          <label>Image (optional)</label>
          <input type="file" name="image" accept="image/jpg,image/jpeg,image/png" style="padding:6px 11px;">
        </div>
      </div>

      <div style="padding:0 18px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--accent);">
        Location
      </div>
      <div class="fgrid">
        <div class="fg s2">
          <label>Venue / Location</label>
          <input type="text" name="location" placeholder="Kozhikode Convention Centre"
                 value="{{ old('form') === 'create' ? old('location') : '' }}">
        </div>
      </div>

      <div style="padding:0 18px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--accent);">
        Seats
      </div>
      <div class="fgrid">
        <div class="fg">
          <label>Total Seats</label>
          <input type="number" name="total_seats" placeholder="e.g. 100" min="1" max="1000"
                 value="{{ old('form') === 'create' ? old('total_seats') : '' }}" required>
        </div>
        <div class="fg">
          <label>Registration Deadline</label>
          <input type="date" name="registration_deadline" id="addEventDeadline"
                 value="{{ old('form') === 'create' ? old('registration_deadline') : '' }}">
        </div>
      </div>

      <div style="padding:8px 18px;">
        <span class="badge bap">All events are FREE & Physical</span>
      </div>

      <div class="ff">
        <button type="button" class="btn bo" onclick="resetAddEventModal(); cm('addEventM')">Cancel</button>
        <button type="submit" class="btn bp"><i class="fas fa-calendar-plus"></i> Create Event</button>
      </div>
    </form>
  </div>
</div>

{{-- ══ EDIT EVENT MODAL ══ --}}
<div id="editEventM" class="mo">
  <div class="md" style="width:600px">
    <div class="mh">
      <span class="mt2">Edit Event</span>
      <div class="mc3" onclick="resetEditEventModal(); cm('editEventM')"><i class="fas fa-times"></i></div>
    </div>
    <form method="POST" action="{{ route('district-admin.events.update') }}" enctype="multipart/form-data" id="editEventForm">
      @csrf
      <input type="hidden" name="form" value="edit">
      <input type="hidden" name="id" id="editEventId" value="{{ old('form') === 'edit' ? old('id') : '' }}">

      @if ($errors->any() && old('form') === 'edit')
        <div id="editEventError" style="margin:14px 18px 0;padding:10px 12px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:8px;font-size:12px;color:#f87171;">
          <ul style="margin:0;padding-left:16px;">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
          </ul>
        </div>
      @endif

      <div style="padding:14px 18px 0;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--accent);">
        Event Info
      </div>
      <div class="fgrid">
        <div class="fg s2">
          <label>Event Title</label>
          <input type="text" name="title" id="editEventTitle" maxlength="200"
                 value="{{ old('form') === 'edit' ? old('title') : '' }}" required>
        </div>
        <div class="fg s2">
          <label>Description</label>
          <textarea name="description" id="editEventDesc" style="min-height:80px" required>{{ old('form') === 'edit' ? old('description') : '' }}</textarea>
        </div>
        <div class="fg">
          <label>Event Date</label>
          <input type="date" name="event_date" id="editEventDate"
                 value="{{ old('form') === 'edit' ? old('event_date') : '' }}" required>
        </div>
        <div class="fg">
          <label>Event Time</label>
          <input type="time" name="event_time" id="editEventTime"
                 value="{{ old('form') === 'edit' ? old('event_time') : '' }}" required>
        </div>
        <div class="fg s2">
          <label>Venue / Location</label>
          <input type="text" name="location" id="editEventLocation" placeholder="Kozhikode Convention Centre"
                 value="{{ old('form') === 'edit' ? old('location') : '' }}">
        </div>
        <div class="fg">
          <label>Total Seats</label>
          <input type="number" name="total_seats" id="editEventSeats" placeholder="e.g. 100" min="1" max="1000"
                 value="{{ old('form') === 'edit' ? old('total_seats') : '' }}" required>
        </div>
        <div class="fg">
          <label>Registration Deadline</label>
          <input type="date" name="registration_deadline" id="editEventDeadline"
                 value="{{ old('form') === 'edit' ? old('registration_deadline') : '' }}">
        </div>
        <div class="fg s2">
          <label>New Image (optional)</label>
          <input type="file" name="image" accept="image/jpg,image/jpeg,image/png" style="padding:6px 11px;">
        </div>
      </div>

      <div class="ff">
        <button type="button" class="btn bo" onclick="resetEditEventModal(); cm('editEventM')">Cancel</button>
        <button type="submit" class="btn bp"><i class="fas fa-save"></i> Update Event</button>
      </div>
    </form>
  </div>
</div>

@endsection

@section('scripts')
<script>
@if ($errors->any())
  document.addEventListener('DOMContentLoaded', function () {
    @if (old('form') === 'edit')
      om('editEventM');
    @elseif (old('form') === 'create')
      om('addEventM');
    @endif
  });
@endif

// ── Reset "Create Event" modal: clears stale error banner + all inputs ──
function resetAddEventModal() {
  const errBox = document.getElementById('addEventError');
  if (errBox) errBox.remove();

  const form = document.getElementById('addEventForm');
  if (form) {
    form.querySelectorAll('input:not([type=hidden]):not([type=file]), textarea').forEach(el => el.value = '');
    const file = form.querySelector('input[type=file]');
    if (file) file.value = '';
  }
  document.getElementById('addEventDeadline').removeAttribute('max');
}

// ── Reset "Edit Event" modal ──
function resetEditEventModal() {
  const errBox = document.getElementById('editEventError');
  if (errBox) errBox.remove();

  const form = document.getElementById('editEventForm');
  if (form) {
    form.querySelectorAll('input:not([type=hidden]):not([type=file]), textarea').forEach(el => el.value = '');
    const file = form.querySelector('input[type=file]');
    if (file) file.value = '';
  }
  document.getElementById('editEventId').value = '';
  document.getElementById('editEventDeadline').removeAttribute('max');
}

// registration deadline can never be after the event date
document.getElementById('addEventDate').addEventListener('change', function () {
  document.getElementById('addEventDeadline').max = this.value;
});
document.getElementById('editEventDate').addEventListener('change', function () {
  document.getElementById('editEventDeadline').max = this.value;
});

function editEvent(id) {
  resetEditEventModal();
  fetch(`{{ url('/district-admin/events') }}/${id}/detail`, { headers: { 'Accept': 'application/json' } })
    .then(r => {
      if (!r.ok) throw new Error('HTTP ' + r.status);
      return r.json();
    })
    .then(e => {
      document.getElementById('editEventId').value       = id;
      document.getElementById('editEventTitle').value    = e.title;
      document.getElementById('editEventDesc').value     = e.description;
      document.getElementById('editEventDate').value     = e.event_date_raw;
      document.getElementById('editEventTime').value     = e.event_time ? String(e.event_time).slice(0, 5) : '';
      document.getElementById('editEventLocation').value = e.location ?? '';
      // old events saved with 0 seats: leave the field empty so the admin must enter a real number
      document.getElementById('editEventSeats').value    = e.total_seats > 0 ? e.total_seats : '';
      document.getElementById('editEventDeadline').value = e.deadline_raw ?? '';
      document.getElementById('editEventDeadline').max   = e.event_date_raw;
      om('editEventM');
    })
    .catch(err => alert('Failed to load event data! ' + err.message));
}

function updateStatus(id, status) {
  if (!confirm('Change status to ' + status + '?')) { location.reload(); return; }
  document.getElementById('statusVal' + id).value = status;
  document.getElementById('statusForm' + id).submit();
}
</script>
@endsection