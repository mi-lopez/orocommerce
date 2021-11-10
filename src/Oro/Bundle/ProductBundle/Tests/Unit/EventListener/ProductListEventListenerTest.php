<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\BuildQueryProductListEvent;
use Oro\Bundle\ProductBundle\Event\BuildResultProductListEvent;
use Oro\Bundle\ProductBundle\EventListener\ProductListEventListener;
use Oro\Bundle\ProductBundle\Model\ProductView;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;

class ProductListEventListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ImagePlaceholderProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $imagePlaceholderProvider;

    /** @var ProductListEventListener */
    private $listener;

    protected function setUp(): void
    {
        $this->imagePlaceholderProvider = $this->createMock(ImagePlaceholderProviderInterface::class);

        $this->listener = new ProductListEventListener($this->imagePlaceholderProvider);
    }

    public function testOnBuildQuery(): void
    {
        $query = $this->createMock(SearchQueryInterface::class);

        $query->expects(self::exactly(8))
            ->method('addSelect')
            ->withConsecutive(
                ['text.type'],
                ['text.sku'],
                ['text.names_LOCALIZATION_ID as name'],
                ['text.image_product_large as image'],
                ['text.primary_unit as unit'],
                ['text.product_units'],
                ['integer.newArrival'],
                ['integer.variant_fields_count']
            )
            ->willReturnSelf();

        $this->listener->onBuildQuery(new BuildQueryProductListEvent('test_list', $query));
    }

    public function testOnBuildResult(): void
    {
        $noImagePath = '/path/no_image.jpg';
        $productData = [
            1 => [
                'id'                   => 1,
                'type'                 => Product::TYPE_CONFIGURABLE,
                'sku'                  => 'p1',
                'name'                 => 'product 1',
                'image'                => 'http://product1',
                'unit'                 => 'items',
                'product_units'        => serialize(['items' => 0, 'set' => 2]),
                'newArrival'           => 1,
                'variant_fields_count' => 3
            ],
            2 => [
                'id'                   => 2,
                'type'                 => Product::TYPE_SIMPLE,
                'sku'                  => 'p2',
                'name'                 => 'product 2',
                'image'                => '',
                'unit'                 => 'items',
                'product_units'        => '',
                'newArrival'           => 0,
                'variant_fields_count' => ''
            ]
        ];
        $productView1 = $this->createMock(ProductView::class);
        $productView2 = $this->createMock(ProductView::class);
        $productViews = [1 => $productView1, 2 => $productView2];

        $productView1->expects(self::exactly(9))
            ->method('set')
            ->withConsecutive(
                ['type', self::identicalTo(Product::TYPE_CONFIGURABLE)],
                ['sku', self::identicalTo('p1')],
                ['name', self::identicalTo('product 1')],
                ['hasImage', self::identicalTo(true)],
                ['image', self::identicalTo('http://product1')],
                ['unit', self::identicalTo('items')],
                ['product_units', self::identicalTo(['items' => 0, 'set' => 2])],
                ['newArrival', self::identicalTo(true)],
                ['variant_fields_count', self::identicalTo(3)]
            );
        $productView2->expects(self::exactly(9))
            ->method('set')
            ->withConsecutive(
                ['type', self::identicalTo(Product::TYPE_SIMPLE)],
                ['sku', self::identicalTo('p2')],
                ['name', self::identicalTo('product 2')],
                ['hasImage', self::identicalTo(false)],
                ['image', self::identicalTo($noImagePath)],
                ['unit', self::identicalTo('items')],
                ['product_units', self::identicalTo([])],
                ['newArrival', self::identicalTo(false)],
                ['variant_fields_count', self::identicalTo(null)]
            );

        $this->imagePlaceholderProvider->expects(self::once())
            ->method('getPath')
            ->with('product_large')
            ->willReturn($noImagePath);

        $this->listener->onBuildResult(new BuildResultProductListEvent('test_list', $productData, $productViews));
    }
}
