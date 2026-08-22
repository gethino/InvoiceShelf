<?php

namespace App\Http\Controllers\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\EmailLog;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentPdfController extends Controller
{
    public function getPdf(EmailLog $emailLog, Request $request)
    {
        $payment = $emailLog->mailable;
        abort_unless($payment instanceof Payment, 404);
        abort_if($emailLog->isExpired(), 403, 'Link Expired.');

        if ($request->has('preview')) {
            return $payment->getPDFData();
        }

        if ($request->has('pdf')) {
            return $payment->getGeneratedPDFOrStream('payment');
        }

        return view('app')->with([
            'customer_logo' => get_company_setting('customer_portal_logo', $payment->company_id),
            'current_theme' => get_company_setting('customer_portal_theme', $payment->company_id),
        ]);
    }

    public function getPayment(EmailLog $emailLog)
    {
        $payment = $emailLog->mailable;
        abort_unless($payment instanceof Payment, 404);
        abort_if($emailLog->isExpired(), 403, 'Link Expired.');

        return new PaymentResource($payment);
    }
}
