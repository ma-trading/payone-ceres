<?php //strict

namespace Payone\Helper;

use Payone\Methods\PayoneInvoicePaymentMethod;
use Payone\Methods\PayonePaydirektPaymentMethod;
use Payone\Methods\PayonePayolutionInstallmentPaymentMethod;
use Payone\Methods\PayonePayPalPaymentMethod;
use Payone\Methods\PayoneRatePayInstallmentPaymentMethod;
use Payone\Methods\PayoneSofortPaymentMethod;
use Plenty\Modules\Payment\Method\Contracts\PaymentMethodRepositoryContract;

/**
 * Class PaymentHelper
 *
 * @package Payone\Helper
 */
class PaymentHelper
{

    /**
     * @var PaymentMethodRepositoryContract
     */
    private $paymentMethodRepo;

    /**
     * PaymentHelper constructor.
     *
     * @param PaymentMethodRepositoryContract $paymentMethodRepo
     */
    public function __construct(PaymentMethodRepositoryContract $paymentMethodRepo)
    {
        $this->paymentMethodRepo = $paymentMethodRepo;
    }

    /**
     * Get the ID of the Payone payment method
     *
     * @return mixed
     */
    public function getPayoneMopId()
    {
        $paymentMethods = $this->paymentMethodRepo->allForPlugin('Payone');

        if (!$paymentMethods) {
            return 'no_paymentmethod_found';
        }
        foreach ($paymentMethods as $paymentMethod) {
            if (in_array($paymentMethod->paymentKey, $this->getPayonePaymentCodes())) {
                return $paymentMethod->id;
            }
        }

        return 'no_paymentmethod_found';
    }

    public function getPayonePaymentCodes()
    {
        return [
            PayoneInvoicePaymentMethod::PAYMENT_CODE,
            PayonePaydirektPaymentMethod::PAYMENT_CODE,
            PayonePayolutionInstallmentPaymentMethod::PAYMENT_CODE,
            PayonePayPalPaymentMethod::PAYMENT_CODE,
            PayoneRatePayInstallmentPaymentMethod::PAYMENT_CODE,
            PayoneSofortPaymentMethod::PAYMENT_CODE,
        ];
    }
}
