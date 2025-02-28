<?php

namespace Oro\Bundle\ProductBundle\Model;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

/**
 * Represents a product line item.
 */
class ProductLineItem implements ProductLineItemInterface, ProductLineItemsHolderAwareInterface
{
    /**
     * @var mixed
     */
    protected $identifier;

    /**
     * @var Product
     */
    protected $product;

    /**
     * @var ProductUnit
     */
    protected $unit;

    /**
     * @var float
     */
    protected $quantity = 1;

    private ?ProductLineItemsHolderInterface $lineItemsHolder = null;

    /**
     * @param mixed $identifier
     */
    public function __construct($identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityIdentifier()
    {
        return $this->identifier;
    }

    /**
     * {@inheritDoc}
     */
    public function getProductHolder()
    {
        return $this;
    }

    /**
     * @return ProductUnit
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * @param ProductUnit $unit
     * @return $this
     */
    public function setUnit(ProductUnit $unit)
    {
        $this->unit = $unit;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getProductUnit()
    {
        return $this->getUnit();
    }

    /**
     * {@inheritDoc}
     */
    public function getProductUnitCode()
    {
        return $this->unit ? $this->unit->getCode() : null;
    }

    /**
     * {@inheritDoc}
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param float $quantity
     * @return $this
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * {@inheritDoc}
     */
    public function getParentProduct()
    {
        return null;
    }

    /**
     * @param Product $product
     * @return $this
     */
    public function setProduct(Product $product)
    {
        $this->product = $product;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getProductSku()
    {
        return $this->product ? $this->product->getSku() : null;
    }

    public function getLineItemsHolder(): ?ProductLineItemsHolderInterface
    {
        return $this->lineItemsHolder;
    }

    public function setLineItemsHolder(?ProductLineItemsHolderInterface $lineItemsHolder): ProductLineItem
    {
        $this->lineItemsHolder = $lineItemsHolder;

        return $this;
    }
}
