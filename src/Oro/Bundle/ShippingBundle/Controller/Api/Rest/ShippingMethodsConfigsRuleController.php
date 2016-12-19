<?php

namespace Oro\Bundle\ShippingBundle\Controller\Api\Rest;

use Doctrine\Common\Persistence\ObjectManager;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Symfony\Component\HttpFoundation\Response;

/**
 * @RouteResource("shippingrules")
 * @NamePrefix("oro_api_")
 */
class ShippingMethodsConfigsRuleController extends RestController implements ClassResourceInterface
{
    /**
     * Enable shipping rule
     *
     * Returns
     * - HTTP_OK (200)
     *
     * @Get(
     *      "/shippingrules/{id}/enable",
     *      requirements={"version"="latest|v1"},
     *      defaults={"version"="latest", "_format"="json"}
     * )
     * @ApiDoc(description="Enable Shipping Rule", resource=true)
     * @AclAncestor("oro_shipping_methods_configs_rule_update")
     *
     * @return Response
     */
    public function enableAction($id)
    {
        /** @var ShippingMethodsConfigsRule $shippingRule */
        $shippingRule = $this->getManager()->find($id);

        if ($shippingRule) {
            $shippingRule->getRule()->setEnabled(true);
            /** @var ObjectManager $objectManager */
            $objectManager = $this->getManager()->getObjectManager();
            $objectManager->persist($shippingRule);
            $objectManager->flush();
            $view = $this->view(
                [
                    'message'    => $this->get('translator')->trans('oro.shipping.notification.channel.enabled'),
                    'successful' => true,
                ],
                Codes::HTTP_OK
            );
        } else {
            /** @var View $view */
            $view = $this->view(null, Codes::HTTP_NOT_FOUND);
        }


        return $this->handleView(
            $view
        );
    }

    /**
     * Disable shipping rule
     *
     * Returns
     * - HTTP_OK (200)
     *
     * @Get(
     *      "/shippingrules/{id}/disable",
     *      requirements={"version"="latest|v1"},
     *      defaults={"version"="latest", "_format"="json"}
     * )
     * @ApiDoc(description="Disable Shipping Rule", resource=true)
     * @AclAncestor("oro_shipping_methods_configs_rule_update")
     *
     * @return Response
     */
    public function disableAction($id)
    {
        /** @var ShippingMethodsConfigsRule $shippingRule */
        $shippingRule = $this->getManager()->find($id);

        if ($shippingRule) {
            $shippingRule->getRule()->setEnabled(false);
            /** @var ObjectManager $objectManager */
            $objectManager = $this->getManager()->getObjectManager();
            $objectManager->persist($shippingRule);
            $objectManager->flush();
            $view = $this->view(
                [
                    'message'    => $this->get('translator')->trans('oro.shipping.notification.channel.disabled'),
                    'successful' => true,
                ],
                Codes::HTTP_OK
            );
        } else {
            /** @var View $view */
            $view = $this->view(null, Codes::HTTP_NOT_FOUND);
        }


        return $this->handleView(
            $view
        );
    }

    /**
     * Rest delete
     *
     * @ApiDoc(
     *      description="Delete Shipping Rule",
     *      resource=true
     * )
     * @Acl(
     *      id="oro_shipping_rule_delete",
     *      type="entity",
     *      permission="DELETE",
     *      class="OroShippingBundle:ShippingMethodsConfigsRule"
     * )
     *
     * @param int $id
     * @return Response
     *
     */
    /*public function deleteAction($id)
    {
        return $this->handleDeleteRequest($id);
    }*/

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->get('oro_shipping.shipping_rule.manager.api');
    }

    /**
     * {@inheritdoc}
     */
    public function getForm()
    {
        throw new \LogicException('This method should not be called');
    }

    /**
     * {@inheritdoc}
     */
    public function getFormHandler()
    {
        throw new \LogicException('This method should not be called');
    }
}
