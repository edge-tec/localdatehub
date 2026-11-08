<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Modules\Billing\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class InvoiceWebController extends Controller
{
    public function show(Request $request, Invoice $invoice)
    {
        $user = Auth::user();

        // Check ownership or admin billing permission
        $canView = $user && ($user->id === $invoice->user_id || $user->hasPermission('billing.view') || in_array($user->role, ['super_admin', 'compliance_admin']));

        if (! $canView) {
            throw new AccessDeniedHttpException('You do not have permission to view this invoice.');
        }

        $platformRemit = config('monetization.platform_remit', [
            'company_name' => 'LocalAdZone Operations, LLC',
            'address' => '100 Enterprise Way, Suite 400',
            'city' => 'Wilmington',
            'state' => 'DE',
            'zip' => '19801',
            'country' => 'United States',
            'support_email' => 'billing@localadzone.com',
        ]);

        return view('pages.billing.invoice', [
            'invoice' => $invoice,
            'remit' => $platformRemit,
        ]);
    }
}