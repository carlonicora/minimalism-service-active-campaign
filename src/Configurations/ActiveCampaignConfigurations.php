<?php
namespace Carlonicora\Minimalism\Services\ActiveCampaign\Configurations;

use CarloNicora\Minimalism\Core\Services\Abstracts\AbstractServiceConfigurations;
use Carlonicora\Minimalism\Core\Services\Exceptions\ConfigurationException;

class ActiveCampaignConfigurations extends AbstractServiceConfigurations {
    /** @var string  */
    public string $url;

    /** @var string  */
    public string $key;

    /** @var string  */
    public string $listId;

    /** @var string  */
    public string $unsubscribeField;

    /**
     * activeCampaignConfigurations constructor.
     * @throws ConfigurationException
     */
    public function __construct() {
        if (!($this->url = getenv('MINIMALISM_SERVICE_ACTIVECAMPAIGN_URL'))){
            throw new ConfigurationException('activeCampaign', 'MINIMALISM_SERVICE_ACTIVECAMPAIGN_URL is a required configuration');
        }

        if (!($this->key = getenv('MINIMALISM_SERVICE_ACTIVECAMPAIGN_KEY'))){
            throw new ConfigurationException('activeCampaign', 'MINIMALISM_SERVICE_ACTIVECAMPAIGN_KEY is a required configuration');
        }

        if (!($this->listId = getenv('MINIMALISM_SERVICE_ACTIVECAMPAIGN_LISTID'))){
            throw new ConfigurationException('activeCampaign', 'MINIMALISM_SERVICE_ACTIVECAMPAIGN_LISTID is a required configuration');
        }

        if (!($this->unsubscribeField = getenv('MINIMALISM_SERVICE_ACTIVECAMPAIGN_UNSUBSCRIBE_LINK_FIELD'))){
            throw new ConfigurationException('activeCampaign', 'MINIMALISM_SERVICE_ACTIVECAMPAIGN_UNSUBSCRIBE_LINK_FIELD is a required configuration');
        }

        if (!getenv('MINIMALISM_SERVICE_MYSQL') || !getenv('ac')) {
            throw new ConfigurationException('activeCampaign', 'MINIMALISM_SERVICE_MYSQL and "ac" configurations are required');
        }
    }
}