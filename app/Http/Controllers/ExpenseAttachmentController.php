<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseAttachment;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ExpenseAttachmentController extends Controller
{
    public function show(Expense $expense, ExpenseAttachment $attachment): View|Response
    {
        Gate::authorize('view');

        if ((int) $attachment->expense_id !== (int) $expense->id) {
            abort(404);
        }

        return view('expenses.attachment-preview', [
            'expense' => $expense,
            'attachment' => $attachment,
        ]);
    }
}
