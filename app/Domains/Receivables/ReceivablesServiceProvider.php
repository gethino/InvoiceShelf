<?php

namespace App\Domains\Receivables;

use App\Adapters\Receivables\LaravelPaymentEmailSender;
use App\Adapters\Receivables\LegacyInvoiceBalanceUpdater;
use App\Adapters\Receivables\LegacyPaymentNumberAssigner;
use App\Adapters\Receivables\MoneyPaymentExchangeRateRecorder;
use App\Domains\Receivables\Application\PaymentService;
use App\Domains\Receivables\Contracts\InvoiceBalanceUpdater;
use App\Domains\Receivables\Contracts\PaymentEmailSender;
use App\Domains\Receivables\Contracts\PaymentExchangeRateRecorder;
use App\Domains\Receivables\Contracts\PaymentNumberAssigner;
use App\Domains\Receivables\Contracts\PaymentPdfDataProvider;
use App\Domains\Receivables\Models\Payment;
use App\Domains\Receivables\Models\PaymentMethod;
use App\Domains\Receivables\Policies\PaymentMethodPolicy;
use App\Domains\Receivables\Policies\PaymentPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class ReceivablesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentPdfDataProvider::class, PaymentService::class);
        $this->app->bind(InvoiceBalanceUpdater::class, LegacyInvoiceBalanceUpdater::class);
        $this->app->bind(PaymentNumberAssigner::class, LegacyPaymentNumberAssigner::class);
        $this->app->bind(PaymentExchangeRateRecorder::class, MoneyPaymentExchangeRateRecorder::class);
        $this->app->bind(PaymentEmailSender::class, LaravelPaymentEmailSender::class);
    }

    public function boot(): void
    {
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(PaymentMethod::class, PaymentMethodPolicy::class);
        Gate::define('send payment', [PaymentPolicy::class, 'send']);
        Gate::define('delete multiple payments', [PaymentPolicy::class, 'deleteMultiple']);
    }
}
