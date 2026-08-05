<?php

namespace App\Services;

use App\Domains\Accounts\Models\CompanySetting;
use App\Platform\Pdf\Facades\Pdf;
use App\Platform\Pdf\Rendering\PdfPageSetup;
use Illuminate\Support\Facades\App;

class CustomerStatementPdfService
{
    public function render(array $statement)
    {
        $customer = $statement['customer'];
        $company = $customer->company;

        App::setLocale(CompanySetting::getSetting('language', $company->id));

        view()->share([
            'statement' => $statement,
            'customer' => $customer,
            'company' => $company,
            'currency' => $statement['currency'],
            'logo' => $company->logo_path,
        ]);

        return Pdf::loadView('app.pdf.reports.customer-statement', [], PdfPageSetup::forReports());
    }
}
