<?php

namespace Payone\Providers;

use Payone\Helper\PaymentHelper;
use Payone\Methods\PayoneCODPaymentMethod;
use Payone\Services\SessionStorageService;
use Plenty\Modules\Account\Address\Contracts\AddressRepositoryContract;
use Plenty\Modules\Basket\Models\Basket;
use Plenty\Modules\Basket\Models\BasketItem;
use Plenty\Modules\Item\Item\Contracts\ItemRepositoryContract;
use Plenty\Modules\Item\Item\Models\Item;
use Plenty\Modules\Item\Item\Models\ItemText;
use Plenty\Modules\Order\Shipping\Countries\Contracts\CountryRepositoryContract;
use Plenty\Modules\Order\Shipping\Information\Contracts\ShippingInformationRepositoryContract;

/**
 * Class ApiRequestDataProvider
 */
class ApiRequestDataProvider
{

    /**
     * @var PaymentHelper
     */
    private $paymentHelper;

    /**
     * @var AddressRepositoryContract
     */
    private $addressRepo;

    /**
     * @var SessionStorageService
     */
    private $sessionStorage;

    /**
     * ApiRequestDataProvider constructor.
     * @param PaymentHelper $paymentHelper
     * @param AddressRepositoryContract $addressRepo
     * @param SessionStorageService $sessionStorage
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        AddressRepositoryContract $addressRepo,
        SessionStorageService $sessionStorage
    ) {
        $this->paymentHelper = $paymentHelper;
        $this->addressRepo = $addressRepo;
        $this->sessionStorage = $sessionStorage;
    }

    /**
     * Fill and return the Paypal parameters
     *
     * @param Basket $basket
     *
     * @return array
     */
    public function getPreAuthData(Basket $basket)
    {
        $requestParams = [];
        $paymentCode = PayoneCODPaymentMethod::PAYMENT_CODE;
        $requestParams['context'] = $this->paymentHelper->getApiContextParams($paymentCode);

        /** @var Basket $basket */
        $requestParams['basket'] = $basket;

        $requestParams['basketItems'] = $this->getCartItemData($basket);
        $requestParams['shippingAddress'] = $this->getShippingData();
        $requestParams['shippingProvider'] = $this->getShippingProviderData($basket->orderId);
        $requestParams['country'] = $this->getCountryData($basket);

        return $requestParams;
    }

    /**
     * @return array
     */
    private function getShippingData()
    {
        $data = [];
        $shippingAddressId = $this->getShippingAddressId();

        if ($shippingAddressId === false) {
            return $data;
        }

        $shippingAddress = $this->addressRepo->findAddressById($shippingAddressId);
        $data['town'] = $shippingAddress->town;
        $data['postalCode'] = $shippingAddress->postalCode;
        $data['firstname'] = $shippingAddress->firstName;
        $data['lastname'] = $shippingAddress->lastName;
        $data['street'] = $shippingAddress->street;
        $data['houseNumber'] = $shippingAddress->houseNumber;

        return $data;
    }

    /**
     * @param Basket $basket
     *
     * @return array
     */
    private function getCartItemData(Basket $basket)
    {
        /** @var ItemRepositoryContract $itemContract */
        $itemContract = pluginApp(ItemRepositoryContract::class);
        $items = [];
        /** @var BasketItem $basketItem */
        foreach ($basket->basketItems as $basketItem) {
            /** @var Item $item */
            $item = $itemContract->show($basketItem->itemId);

            $basketItem = $basketItem->getAttributes();

            /** @var ItemText $itemText */
            $itemText = $item->texts;

            $basketItem['name'] = $itemText->first()->name1;

            $items[] = $basketItem;
        }

        return $items;
    }

    /**
     * @param Basket $basket
     *
     * @return array
     */
    private function getCountryData(Basket $basket)
    {
        /** @var CountryRepositoryContract $countryRepo */
        $countryRepo = pluginApp(CountryRepositoryContract::class);

        // Fill the country for PayPal parameters
        $country['isoCode2'] = $countryRepo->findIsoCode($basket->shippingCountryId, 'iso_code_2');

        return $country;
    }

    /**
     * @return bool|mixed
     */
    private function getShippingAddressId()
    {
        $shippingAddressId = $this->sessionStorage->getSessionValue(SessionStorageService::DELIVERY_ADDRESS_ID);

        if ($shippingAddressId == -99) {
            $shippingAddressId = $this->sessionStorage->getSessionValue(SessionStorageService::BILLING_ADDRESS_ID);
        }

        return $shippingAddressId ? $shippingAddressId : false;
    }

    /**
     * @param int $orderId
     *
     * @return array
     */
    private function getShippingProviderData(int $orderId)
    {
        /** @var ShippingInformationRepositoryContract $shippingRepo */
        $shippingRepo = pluginApp(ShippingInformationRepositoryContract::class);
        $shippingInfo = $shippingRepo->getShippingInformationByOrderId($orderId);

        return $shippingInfo->toArray();
    }
}