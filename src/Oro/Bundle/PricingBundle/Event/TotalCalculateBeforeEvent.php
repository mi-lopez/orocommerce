<?php

namespace Oro\Bundle\PricingBundle\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

class TotalCalculateBeforeEvent extends Event
{
    /** Event can be used for prepare entity from request for dynamic totals calculation */
    const NAME = 'oro_pricing.total_calculate_before_event';

    /** @var object */
    protected $entity;

    /** @var Request */
    protected $request;

    public function __construct($entity, Request $request)
    {
        $this->entity = $entity;
        $this->request = $request;
    }

    /**
     * @return object
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }
}
