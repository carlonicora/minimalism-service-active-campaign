<?php
namespace CarloNicora\Minimalism\Services\ActiveCampaign\Configurations;

use CarloNicora\Minimalism\Core\Events\MinimalismErrorEvents;
use CarloNicora\Minimalism\Core\Services\Abstracts\AbstractServiceConfigurations;
use CarloNicora\Minimalism\Core\Services\Exceptions\ConfigurationException;
use Exception;

class ActiveCampaignConfigurations extends AbstractServiceConfigurations {
    /** @var string  */
    public string $url;

    /** @var string  */
    public string $key;

    /** @var string  */
    public string $listId;

    /**
     * activeCampaignConfigurations constructor.
     * @throws Exception
     */
    public function __construct() {
        if (!($this->url = getenv('MINIMALISM_SERVICE_ACTIVECAMPAIGN_URL'))){
            MinimalismErrorEvents::CONFIGURATION_ERROR('MINIMALISM_SERVICE_ACTIVECAMPAIGN_URL is a required configuration')
                ->throw(ConfigurationException::class);
        }

        if (!($this->key = getenv('MINIMALISM_SERVICE_ACTIVECAMPAIGN_KEY'))){
            MinimalismErrorEvents::CONFIGURATION_ERROR('MINIMALISM_SERVICE_ACTIVECAMPAIGN_KEY is a required configuration')
                ->throw(ConfigurationException::class);
        }

        if (!($this->listId = getenv('MINIMALISM_SERVICE_ACTIVECAMPAIGN_LISTID'))){
            MinimalismErrorEvents::CONFIGURATION_ERROR('MINIMALISM_SERVICE_ACTIVECAMPAIGN_LISTID is a required configuration')
                ->throw(ConfigurationException::class);
        }

        if (!getenv('MINIMALISM_SERVICE_MYSQL') || !getenv('Ac')) {
            MinimalismErrorEvents::CONFIGURATION_ERROR('MINIMALISM_SERVICE_MYSQL and "Ac" configurations are required')
                ->throw(ConfigurationException::class);
        }
    }
}