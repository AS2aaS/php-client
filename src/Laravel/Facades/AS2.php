<?php

declare(strict_types=1);

namespace AS2aaS\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * AS2 Facade for Laravel
 * 
 * @method static \AS2aaS\Modules\Partners partners()
 * @method static \AS2aaS\Modules\Messages messages()
 * @method static \AS2aaS\Modules\Certificates certificates()
 * @method static \AS2aaS\Modules\Accounts accounts()
 * @method static \AS2aaS\Modules\Tenants tenants()
 * @method static \AS2aaS\Modules\Webhooks webhooks()
 * @method static \AS2aaS\Modules\Billing billing()
 * @method static \AS2aaS\Modules\Sandbox sandbox()
 * @method static \AS2aaS\Modules\Partnerships partnerships()
 * @method static \AS2aaS\Modules\Utils utils()
 */
class AS2 extends Facade
{
    /**
     * Get the registered name of the component
     */
    protected static function getFacadeAccessor(): string
    {
        return 'as2aas';
    }
}
