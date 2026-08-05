<?php

declare(strict_types=1);

namespace App\Platform\Persistence;

use App\Models\Address;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\Company;
use App\Models\CompanyInvitation;
use App\Models\CompanySetting;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\CustomField;
use App\Models\CustomFieldValue;
use App\Models\EmailLog;
use App\Models\Estimate;
use App\Models\EstimateItem;
use App\Models\ExchangeRateLog;
use App\Models\ExchangeRateProvider;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FileDisk;
use App\Models\ImpersonationLog;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Item;
use App\Models\Note;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\PaymentMethod;
use App\Models\RecurringInvoice;
use App\Models\Setting;
use App\Models\Tax;
use App\Models\TaxType;
use App\Models\Transaction;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserSetting;
use App\Platform\Modules\Models\MarketplaceCredential;
use App\Platform\Modules\Models\MarketplaceOperation;
use App\Platform\Modules\Models\Module;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use InvalidArgumentException;
use Silber\Bouncer\Database\Ability;
use Silber\Bouncer\Database\Role;

/**
 * Stable database identities for Eloquent models.
 *
 * Model namespaces are an implementation detail and will change as contexts
 * move into app/Domains and app/Platform. These aliases are persisted instead,
 * so future namespace refactors cannot invalidate polymorphic records.
 */
final class ModelIdentityMap
{
    /**
     * @return array<string, class-string<Model>>
     */
    public static function aliases(): array
    {
        return [
            'address' => Address::class,
            'ai_conversation' => AiConversation::class,
            'ai_message' => AiMessage::class,
            'company' => Company::class,
            'company_invitation' => CompanyInvitation::class,
            'company_setting' => CompanySetting::class,
            'country' => Country::class,
            'currency' => Currency::class,
            'custom_field' => CustomField::class,
            'custom_field_value' => CustomFieldValue::class,
            'customer' => Customer::class,
            'email_log' => EmailLog::class,
            'estimate' => Estimate::class,
            'estimate_item' => EstimateItem::class,
            'exchange_rate_log' => ExchangeRateLog::class,
            'exchange_rate_provider' => ExchangeRateProvider::class,
            'expense' => Expense::class,
            'expense_category' => ExpenseCategory::class,
            'file_disk' => FileDisk::class,
            'impersonation_log' => ImpersonationLog::class,
            'invoice' => Invoice::class,
            'invoice_item' => InvoiceItem::class,
            'item' => Item::class,
            'marketplace_credential' => MarketplaceCredential::class,
            'marketplace_operation' => MarketplaceOperation::class,
            'module' => Module::class,
            'note' => Note::class,
            'payment' => Payment::class,
            'payment_allocation' => PaymentAllocation::class,
            'payment_method' => PaymentMethod::class,
            'recurring_invoice' => RecurringInvoice::class,
            'setting' => Setting::class,
            'tax' => Tax::class,
            'tax_type' => TaxType::class,
            'transaction' => Transaction::class,
            'unit' => Unit::class,
            'user' => User::class,
            'user_setting' => UserSetting::class,
            'bouncer_ability' => Ability::class,
            'bouncer_role' => Role::class,
        ];
    }

    public static function enforce(): void
    {
        Relation::enforceMorphMap(self::aliases());
    }

    /**
     * @param  class-string<Model>  $model
     */
    public static function aliasFor(string $model): string
    {
        $alias = array_search($model, self::aliases(), true);

        if (! is_string($alias)) {
            throw new InvalidArgumentException("Model [{$model}] has no stable database identity.");
        }

        return $alias;
    }

    /**
     * Preserve the model discriminator exposed by the existing v1 API.
     */
    public static function publicType(string $databaseType): string
    {
        $model = self::aliases()[$databaseType] ?? null;

        if ($model === null || str_starts_with($databaseType, 'bouncer_')) {
            return $databaseType;
        }

        return 'App\\Models\\'.class_basename($model);
    }
}
