<?php
namespace carlonicora\minimalism\services\activeCampaign\factories;

use carlonicora\minimalism\core\services\abstracts\abstractServiceFactory;
use carlonicora\minimalism\core\services\exceptions\configurationException;
use carlonicora\minimalism\core\services\factories\servicesFactory;
use carlonicora\minimalism\services\activeCampaign\activeCampaign;
use carlonicora\minimalism\services\activeCampaign\configurations\activeCampaignConfigurations;

class serviceFactory extends abstractServiceFactory {
    /**
     * serviceFactory constructor.
     * @param servicesFactory $services
     * @throws configurationException
     */
    public function __construct(servicesFactory $services) {
        $this->configData = new activeCampaignConfigurations();

        parent::__construct($services);
    }

    /**
     * @param servicesFactory $services
     * @return activeCampaign
     */
    public function create(servicesFactory $services): activeCampaign {
        return new activeCampaign($this->configData, $services);
    }
}