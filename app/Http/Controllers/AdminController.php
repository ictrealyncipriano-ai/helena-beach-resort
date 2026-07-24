<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Illuminate\Routing\Controller;

class AdminController extends Controller
{
    public function activateAllFaqs()
    {
        Faq::query()->update(['is_active' => true]);

        return redirect('/admin/faqs');
    }
}
