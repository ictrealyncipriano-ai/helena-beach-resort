<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Services\ActivityLogger;
use App\Support\PublicCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FaqController extends Controller
{
    public function index(Request $request): View
    {
        $query = Faq::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('question', 'like', "%{$search}%")
                    ->orWhere('answer', 'like', "%{$search}%");
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $faqs = $query->orderBy('sort_order')->paginate(self::ADMIN_PER_PAGE)->withQueryString();

        $faqsData = $faqs->map(function ($faq) {
            return [
                'id' => $faq->id,
                'question' => $faq->question,
                'answer' => $faq->answer,
                'is_active' => (bool) $faq->is_active,
                'sort_order' => $faq->sort_order,
            ];
        })->values();

        return view('admin.faqs.index', compact('faqs', 'faqsData'));
    }

    public function create(): View
    {
        return view('admin.faqs.form', ['faq' => new Faq]);
    }

    public function store(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $data = $this->validated($request);
        $faq = Faq::create($data);

        $logger->record('faq.created', $faq, "FAQ created: {$faq->question}");

        return redirect()->route('admin.faqs.index')
            ->with('success', 'FAQ created successfully.');
    }

    public function edit(Faq $faq): View
    {
        return view('admin.faqs.form', compact('faq'));
    }

    public function update(Request $request, Faq $faq, ActivityLogger $logger): RedirectResponse
    {
        $data = $this->validated($request);
        $faq->update($data);

        $logger->record('faq.updated', $faq, "FAQ updated: {$faq->question}");

        return redirect()->route('admin.faqs.index')
            ->with('success', 'FAQ updated successfully.');
    }

    public function destroy(Faq $faq, ActivityLogger $logger): RedirectResponse
    {
        $faq->delete();

        $logger->record('faq.deleted', $faq, "FAQ deleted: {$faq->question}");

        return redirect()->route('admin.faqs.index')
            ->with('success', 'FAQ deleted successfully.');
    }

    public function activateAll(ActivityLogger $logger): RedirectResponse
    {
        Faq::query()->update(['is_active' => true]);
        PublicCache::flush();

        $logger->record('faq.activated', null, 'All FAQs activated.');

        return redirect()->route('admin.faqs.index')
            ->with('success', 'All FAQs activated successfully.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'question' => 'required|max:255',
            'answer' => 'required',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
