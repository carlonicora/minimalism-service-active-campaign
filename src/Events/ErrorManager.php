<?php
namespace CarloNicora\Minimalism\Services\ActiveCampaign\Events;

use CarloNicora\Minimalism\Core\Events\Abstracts\AbstractErrorEvent;
use CarloNicora\Minimalism\Core\Events\Interfaces\EventInterface;
use CarloNicora\Minimalism\Core\Modules\Interfaces\ResponseInterface;

class ErrorManager extends AbstractErrorEvent
{
    /** @var string  */
    protected string $serviceName = 'miniamlism-service-active-campaign';

    /**
     * @return EventInterface
     */
    public static function USER_JOURNALS_GET_INCORRECT_USER_ID() : EventInterface
    {
        return new self(1, ResponseInterface::HTTP_STATUS_422,'Incorrect user id.');
    }
}