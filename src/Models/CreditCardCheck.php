<?php

namespace Payone\Models;

class CreditCardCheck implements \JsonSerializable
{
    /** @var PaymentConfig */
    private $configRepo;

    /**
     * CreditCardCheck constructor.
     *
     * @param PaymentConfig $configRepo
     */
    public function __construct(
        PaymentConfig $configRepo
    ) {
        $this->configRepo = $configRepo;
    }

    public function createHash($data)
    {
        ksort($data);
        return hash_hmac('sha384', implode('', $data), $this->configRepo->getKey());
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $data = [
            'request' => 'creditcardcheck',
            'responsetype' => 'JSON',
            'mode' => $this->configRepo->getMode(),
            'mid' => $this->configRepo->getMid(),
            'aid' => $this->configRepo->getAid(),
            'portalid' => $this->configRepo->getPortalid(),
            'encoding' => 'UTF-8',
            'storecarddata' => 'yes',
        ];

        $data['hash'] = $this->createHash($data);

        return $data;
    }
}

