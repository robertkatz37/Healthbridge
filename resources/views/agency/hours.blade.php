<x-agency-layout title="Business Hours">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('agency.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Business Hours</li>
    @endslot

    <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
        <div class="card-body p-4">
            <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Weekly Business Hours</h6>

            <form method="POST" action="{{ route('agency.hours.update') }}">
                @csrf @method('PUT')

                @php $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']; @endphp

                @foreach($dayNames as $dayIndex => $dayName)
                    @php $h = $hours->get($dayIndex); @endphp
                    <div class="row g-2 align-items-center mb-3 py-2" style="border-bottom:1px solid var(--hb-gray-200);">
                        <div class="col-4 col-md-2">
                            <span style="font-size:0.9rem;font-weight:600;color:var(--hb-gray-900);">{{ $dayName }}</span>
                        </div>
                        <div class="col-4 col-md-2">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="closed_{{ $dayIndex }}"
                                       name="days[{{ $dayIndex }}][is_closed]" value="1" {{ !$h || $h->is_closed ? 'checked' : '' }}
                                       onchange="document.getElementById('hours_{{ $dayIndex }}').style.display = this.checked ? 'none' : 'flex'">
                                <label class="form-check-label" for="closed_{{ $dayIndex }}" style="font-size:0.8rem;color:var(--hb-gray-600);">Closed</label>
                            </div>
                        </div>
                        <div class="col-4 col-md-8" id="hours_{{ $dayIndex }}" style="display:{{ !$h || $h->is_closed ? 'none' : 'flex' }};gap:0.5rem;">
                            <input type="time" name="days[{{ $dayIndex }}][open_time]" class="hb-form-control" style="max-width:140px;"
                                   value="{{ $h?->open_time ? \Illuminate\Support\Carbon::parse($h->open_time)->format('H:i') : '09:00' }}">
                            <span style="align-self:center;color:var(--hb-gray-600);">to</span>
                            <input type="time" name="days[{{ $dayIndex }}][close_time]" class="hb-form-control" style="max-width:140px;"
                                   value="{{ $h?->close_time ? \Illuminate\Support\Carbon::parse($h->close_time)->format('H:i') : '17:00' }}">
                        </div>
                    </div>
                @endforeach

                <button type="submit" class="btn btn-primary mt-3" style="border-radius:0.75rem;">
                    <i class="bi bi-save me-2"></i>Save Hours
                </button>
            </form>
        </div>
    </div>
</x-agency-layout>
