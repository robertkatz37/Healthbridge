<x-admin-layout title="Menus">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">Menus</li>
    @endslot

    @if(session('status'))
        <div class="hb-alert hb-alert-success mb-4" data-auto-dismiss="5000"><i class="bi bi-check-circle me-2"></i>Done.</div>
    @endif

    <ul class="nav nav-tabs mb-4">
        @foreach(['header' => 'Header', 'footer' => 'Footer', 'mobile' => 'Mobile'] as $slug => $label)
            <li class="nav-item">
                <a class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab" href="#menu-{{ $slug }}">{{ $label }}</a>
            </li>
        @endforeach
    </ul>

    <div class="tab-content">
        @foreach(['header' => 'Header', 'footer' => 'Footer', 'mobile' => 'Mobile'] as $slug => $label)
            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="menu-{{ $slug }}">
                @php $menu = $menus->get($slug); @endphp
                @if(!$menu)
                    <p style="color:var(--hb-gray-600);">The {{ $label }} menu has not been created yet.</p>
                @else
                    <div class="row g-4">
                        <div class="col-lg-5">
                            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                                <div class="card-body p-4">
                                    <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Add Menu Item</h6>
                                    <form method="POST" action="{{ route('admin.cms.menus.items.store', $menu) }}">
                                        @csrf
                                        <label class="hb-form-label">Label</label>
                                        <input type="text" name="label" class="hb-form-control mb-2" required>
                                        <label class="hb-form-label">Link to Page</label>
                                        <select name="cms_page_id" class="hb-form-control mb-2">
                                            <option value="">— None —</option>
                                            @foreach($pages as $page)
                                                <option value="{{ $page->id }}">{{ $page->title }}</option>
                                            @endforeach
                                        </select>
                                        <label class="hb-form-label">Or Custom URL</label>
                                        <input type="text" name="url" class="hb-form-control mb-2" placeholder="/agencies or https://...">
                                        <label class="hb-form-label">Parent (for dropdowns)</label>
                                        <select name="parent_id" class="hb-form-control mb-3">
                                            <option value="">— Top level —</option>
                                            @foreach($menu->items as $existingItem)
                                                <option value="{{ $existingItem->id }}">{{ $existingItem->label }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn-primary w-100" style="border-radius:0.625rem;">Add Item</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <div class="card" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
                                <div class="card-body p-0">
                                    <table class="table mb-0" style="font-size:0.875rem;">
                                        <tbody>
                                            @foreach($menu->items->whereNull('parent_id')->sortBy('sort_order') as $item)
                                                <tr style="border-bottom:1px solid var(--hb-gray-200);">
                                                    <td class="px-3 py-2">
                                                        {{ $item->label }}
                                                        <div style="font-size:0.75rem;color:var(--hb-gray-600);">{{ $item->resolved_url }}</div>
                                                    </td>
                                                    <td class="px-3 py-2 text-end">
                                                        <form method="POST" action="{{ route('admin.cms.menus.items.destroy', $item) }}" class="d-inline" onsubmit="return confirm('Remove this menu item?');">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius:0.5rem;">Remove</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                                @foreach($item->children->sortBy('sort_order') as $child)
                                                    <tr style="border-bottom:1px solid var(--hb-gray-200);background:var(--hb-gray-50);">
                                                        <td class="px-3 py-2 ps-4">
                                                            &mdash; {{ $child->label }}
                                                            <div style="font-size:0.75rem;color:var(--hb-gray-600);">{{ $child->resolved_url }}</div>
                                                        </td>
                                                        <td class="px-3 py-2 text-end">
                                                            <form method="POST" action="{{ route('admin.cms.menus.items.destroy', $child) }}" class="d-inline" onsubmit="return confirm('Remove this menu item?');">
                                                                @csrf @method('DELETE')
                                                                <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius:0.5rem;">Remove</button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</x-admin-layout>
