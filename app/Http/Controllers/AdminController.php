<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Illuminate\Routing\Controller;

/**
 * Utility actions for the admin panel (e.g., bulk operations).
 */
class AdminController extends Controller
{
    /** Quick action to activate all FAQs at once */
    public function activateAllFaqs()
    {
        Faq::query()->update(['is_active' => true]);

        return redirect('/admin/faqs');
    }
}
