<?php

namespace OroB2B\Bundle\TaxBundle\Provider;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;

class TaxationAddressProvider
{
    /**
     * @param TaxationSettingsProvider $settingsProvider
     */
    public function __construct(TaxationSettingsProvider $settingsProvider)
    {
        $this->settingsProvider = $settingsProvider;
    }

    /**
     * @param Order $order
     * @return AbstractAddress
     */
    public function getAddressForTaxation(Order $order)
    {
        $orderAddress = $this->getDestinationAddress($order);

        if (null === $orderAddress) {
            return null;
        }

        $exclusionUsed = false;
        $exclusions = $this->settingsProvider->getBaseAddressExclusions();
        foreach ($exclusions as $exclusion) {
            if ($orderAddress->getCountry() === $exclusion->getCountry() &&
                ($exclusion->getRegion() === null || $exclusion->getRegion() === $orderAddress->getRegion())
            ) {
                $orderAddress = $this->getDestinationAddressByType($order, $exclusion->getOption());
            }
        }

        if (!$exclusionUsed && $this->settingsProvider->isOriginBaseByDefaultAddressType()) {
            return $this->settingsProvider->getOrigin();
        }

        return $orderAddress;
    }

    /**
     * @param Order $order
     * @return OrderAddress|null
     */
    protected function getDestinationAddress(Order $order)
    {
        return $this->getDestinationAddressByType($order, $this->settingsProvider->getDestination());
    }

    /**
     * @param Order $order
     * @param string $type
     * @return OrderAddress|null
     */
    protected function getDestinationAddressByType(Order $order, $type)
    {
        $orderAddress = null;
        switch ($type) {
            case TaxationSettingsProvider::DESTINATION_BILLING_ADDRESS:
                $orderAddress = $order->getBillingAddress();
                break;
            case TaxationSettingsProvider::DESTINATION_SHIPPING_ADDRESS:
                $orderAddress = $order->getShippingAddress();
                break;
        }

        return $orderAddress;
    }

    /**
     * @return AbstractAddress
     */
    public function getOriginAddress()
    {
        return $this->settingsProvider->getOrigin();
    }
}
