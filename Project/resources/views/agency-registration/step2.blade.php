<x-wizard-layout title="Agency Onboarding — Services" :current-step="2">
    <div class="mb-4">
        <h4 class="fw-bold mb-1" style="color:var(--hb-gray-900);font-family:'Fraunces',serif;">What services do you offer?</h4>
        <p style="color:var(--hb-gray-600);font-size:0.9rem;">Add at least one service. You can select from our catalog or add a custom one.</p>
    </div>

    <form method="POST" action="{{ route('agency.register.step2.store') }}" novalidate
          x-data="serviceForm({{ $services->map(fn($s) => ['service_catalog_id' => $s->service_catalog_id, 'name' => $s->name, 'price_from' => $s->price_from])->values()->toJson() ?: '[]' }})">
        @csrf

        <template x-for="(service, index) in services" :key="index">
            <div class="card mb-3" style="border-radius:0.875rem;border:1.5px solid var(--hb-gray-200);">
                <div class="card-body p-3">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-5">
                            <label class="hb-form-label">Service name</label>
                            <input type="text" :name="`services[${index}][name]`" x-model="service.name"
                                   class="hb-form-control" placeholder="e.g. Personal Care" required>
                        </div>
                        <div class="col-md-4">
                            <label class="hb-form-label">From catalog (optional)</label>
                            <select :name="`services[${index}][service_catalog_id]`" x-model="service.service_catalog_id" class="hb-form-control">
                                <option value="">Custom service</option>
                                @foreach($catalog as $item)
                                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="hb-form-label">Price from</label>
                            <input type="number" :name="`services[${index}][price_from]`" x-model="service.price_from"
                                   class="hb-form-control" placeholder="$" min="0" step="0.01">
                        </div>
                        <div class="col-md-1 text-end">
                            <button type="button" class="btn btn-sm btn-outline-danger" @click="remove(index)" style="border-radius:0.5rem;">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <button type="button" class="hb-btn-outline mb-4" style="width:auto;" @click="add()">
            <i class="bi bi-plus-lg me-2"></i>Add another service
        </button>

        <div class="d-flex justify-content-between">
            <a href="{{ route('agency.register.step1') }}" class="hb-btn-outline" style="width:auto;padding:0.7rem 1.75rem;text-decoration:none;">
                <i class="bi bi-arrow-left me-2"></i>Back
            </a>
            <button type="submit" class="hb-btn-primary" style="width:auto;padding:0.7rem 2rem;">
                Continue <i class="bi bi-arrow-right ms-2"></i>
            </button>
        </div>
    </form>

    @push('scripts')
    <script>
        function serviceForm(initial) {
            return {
                services: initial.length ? initial : [{ service_catalog_id: '', name: '', price_from: '' }],
                add() { this.services.push({ service_catalog_id: '', name: '', price_from: '' }); },
                remove(index) {
                    if (this.services.length > 1) this.services.splice(index, 1);
                },
            };
        }
    </script>
    @endpush
</x-wizard-layout>
