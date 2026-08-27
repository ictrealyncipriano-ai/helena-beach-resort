<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PromoCodeController extends Controller
{
    public function index(Request $request): View
    {
        $query = PromoCode::query();

        if ($search = $request->get('search')) {
            $query->where('code', 'like', '%'.strtoupper($search).'%');
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $promoCodes = $query->orderByDesc('id')->paginate(self::ADMIN_PER_PAGE)->withQueryString();

        return view('admin.promo-codes.index', compact('promoCodes'));
    }

    public function create(): View
    {
        return view('admin.promo-codes.form', ['promo' => new PromoCode]);
    }

    public function store(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $data = $this->validated($request);

        $promo = PromoCode::create($data);

        $logger->record('promo.created', $promo, "Promo code {$promo->code} created.", [
            'type' => $promo->type,
            'value' => $promo->value,
        ]);

        return redirect()->route('admin.promo-codes.index')
            ->with('success', 'Promo code created successfully.');
    }

    public function edit(PromoCode $promo): View
    {
        return view('admin.promo-codes.form', compact('promo'));
    }

    public function update(Request $request, PromoCode $promo, ActivityLogger $logger): RedirectResponse
    {
        $data = $this->validated($request, $promo);

        $promo->update($data);

        $logger->record('promo.updated', $promo, "Promo code {$promo->code} updated.", [
            'type' => $promo->type,
            'value' => $promo->value,
        ]);

        return redirect()->route('admin.promo-codes.index')
            ->with('success', 'Promo code updated successfully.');
    }

    public function destroy(PromoCode $promo, ActivityLogger $logger): RedirectResponse
    {
        $promo->delete();

        $logger->record('promo.deleted', $promo, "Promo code {$promo->code} deleted.");

        return redirect()->route('admin.promo-codes.index')
            ->with('success', 'Promo code deleted successfully.');
    }

    private function validated(Request $request, ?PromoCode $promo = null): array
    {
        $data = $request->validate([
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('promo_codes', 'code')->ignore($promo?->id),
            ],
            'type' => ['required', 'in:fixed,percent'],
            'value' => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after:valid_from'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['value'] = formatPrice($data['value'], 2, false);
        $data['min_amount'] = isset($data['min_amount']) && $data['min_amount'] !== null && $data['min_amount'] !== ''
            ? formatPrice($data['min_amount'], 2, false)
            : null;

        foreach (['valid_from', 'valid_until'] as $field) {
            $data[$field] = isset($data[$field]) && $data[$field] !== null && $data[$field] !== ''
                ? Carbon::parse($data[$field])->timezone(config('app.timezone'))->format('Y-m-d H:i:s')
                : null;
        }

        return $data;
    }
}
