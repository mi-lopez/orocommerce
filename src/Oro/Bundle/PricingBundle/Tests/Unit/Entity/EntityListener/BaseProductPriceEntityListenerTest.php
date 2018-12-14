<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\PricingBundle\Entity\BaseProductPrice;
use Oro\Bundle\PricingBundle\Entity\EntityListener\BaseProductPriceEntityListener;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\ProductBundle\Entity\Product;

abstract class BaseProductPriceEntityListenerTest extends AbstractRuleEntityListenerTest
{
    /**
     * @var PreUpdateEventArgs|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $preUpdateEventArgs;

    /**
     * @var BaseProductPriceEntityListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->preUpdateEventArgs = $this->createMock(PreUpdateEventArgs::class);

        parent::setUp();
    }

    protected function tearDown()
    {
        parent::tearDown();

        unset($this->preUpdateEventArgs);
    }

    public function testPostPersist()
    {
        list($baseProductPrice, $product, $priceList) = $this->getEntities();

        $this->assertRecalculateByEntity(1, [], [$product], $priceList->getId());

        $this->listener->postPersist($baseProductPrice);
    }

    public function testPreUpdate()
    {
        list($baseProductPrice, $product, $priceList) = $this->getEntities();

        $this->preUpdateEventArgs->expects($this->any())
            ->method('getEntityChangeSet')
            ->willReturn([]);

        $this->preUpdateEventArgs->expects($this->any())
            ->method('hasChangedField')
            ->willReturnCallback(function ($argument) {
                return BaseProductPriceEntityListener::FIELD_VALUE !== $argument;
            });

        $this->preUpdateEventArgs->expects($this->any())
            ->method('getOldValue')
            ->willReturnCallback(function ($argument) use ($priceList, $product) {
                return (BaseProductPriceEntityListener::FIELD_PRICE_LIST === $argument)
                    ? $priceList
                    : $product;
            });

        $this->assertRecalculateByEntityFieldsUpdate(2, 0, [], [], $product, $priceList->getId());

        $this->listener->preUpdate($baseProductPrice, $this->preUpdateEventArgs);
    }

    public function testPreRemove()
    {
        list($baseProductPrice, $product, $priceList) = $this->getEntities();

        $this->assertRecalculateByEntity(1, [], [$product], $priceList->getId());

        $this->listener->preRemove($baseProductPrice);
    }

    /**
     * @return array
     */
    protected function getEntities()
    {
        /** @var BaseProductPrice $baseProductPrice */
        $baseProductPrice = $this->getEntity(BaseProductPrice::class, ['id' => 1]);
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 2]);
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 3]);

        $baseProductPrice->setProduct($product);
        $baseProductPrice->setPriceList($priceList);

        return [$baseProductPrice, $product, $priceList];
    }
}
