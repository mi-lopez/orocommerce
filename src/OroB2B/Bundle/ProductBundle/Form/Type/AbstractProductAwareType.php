<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Model\ProductHolderInterface;

abstract class AbstractProductAwareType extends AbstractType
{
    /** {@inheritdoc} */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'product' => null,
                'product_holder' => null,
                'product_field' => 'product',
            ]
        );

        $resolver->setAllowedTypes('product', ['OroB2B\Bundle\ProductBundle\Entity\Product', 'null']);
        $resolver->setAllowedTypes(
            'product_holder',
            ['OroB2B\Bundle\ProductBundle\Model\ProductHolderInterface', 'null']
        );
        $resolver->setAllowedTypes('product_field', 'string');
    }

    /**
     * @param FormInterface $form
     * @return null|Product
     */
    protected function getProduct(FormInterface $form)
    {
        $options = $form->getConfig()->getOptions();
        $parent = $form->getParent();

        $productField = $options['product_field'];

        if ($parent->has($productField)) {
            $productData = $parent->get($productField)->getData();
            if ($productData instanceof Product) {
                return $productData;
            }
        }

        /** @var Product $product */
        $product = $options['product'];
        if ($product) {
            return $product;
        }

        /** @var ProductHolderInterface $productHolder */
        $productHolder = $options['product_holder'];
        if ($productHolder) {
            return $productHolder->getProduct();
        }

        return null;
    }
}
