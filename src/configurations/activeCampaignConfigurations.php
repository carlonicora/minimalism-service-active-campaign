<?php
namespace carlonicora\minimalism\services\activeCampaign\configurations;

use carlonicora\minimalism\core\services\abstracts\abstractServiceConfigurations;
use carlonicora\minimalism\core\services\exceptions\configurationException;

class activeCampaignConfigurations extends abstractServiceConfigurations {
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
     * @throws configurationException
     */
    public function __construct() {
        if (!($this->url = getenv('MINIMALISM_SERVICE_ACTIVECAMPAIGN_URL'))){
            throw new configurationException('activeCampaign', 'MINIMALISM_SERVICE_ACTIVECAMPAIGN_URL is a required configuration');
        }

        if (!($this->key = getenv('MINIMALISM_SERVICE_ACTIVECAMPAIGN_KEY'))){
            throw new configurationException('activeCampaign', 'MINIMALISM_SERVICE_ACTIVECAMPAIGN_KEY is a required configuration');
        }

        if (!($this->listId = getenv('MINIMALISM_SERVICE_ACTIVECAMPAIGN_LISTID'))){
            throw new configurationException('activeCampaign', 'MINIMALISM_SERVICE_ACTIVECAMPAIGN_LISTID is a required configuration');
        }

        if (!($this->unsubscribeField = getenv('MINIMALISM_SERVICE_ACTIVECAMPAIGN_UNSUBSCRIBE_LINK_FIELD'))){
            throw new configurationException('activeCampaign', 'MINIMALISM_SERVICE_ACTIVECAMPAIGN_UNSUBSCRIBE_LINK_FIELD is a required configuration');
        }

        if (!getenv('MINIMALISM_SERVICE_MYSQL') || !getenv('ac')) {
            throw new configurationException('activeCampaign', 'MINIMALISM_SERVICE_MYSQL and "ac" configurations are required');
        }
    }
}