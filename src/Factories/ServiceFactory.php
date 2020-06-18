<?php
namespace Carlonicora\Minimalism\Services\ActiveCampaign\Factories;

use carlonicora\minimalism\core\services\abstracts\abstractServiceFactory;
use Carlonicora\Minimalism\Core\Services\Exceptions\ConfigurationException;
use Carlonicora\Minimalism\Core\Services\Factories\ServicesFactory;
use Carlonicora\Minimalism\Services\ActiveCampaign\ActiveCampaign;
use Carlonicora\Minimalism\Services\ActiveCampaign\Configurations\ActiveCampaignConfigurations;

class ServiceFactory extends AbstractServiceFactory {
    /**
     * serviceFactory constructor.
     * @param servicesFactory $services
     * @throws configurationException
     */
    public function __construct(servicesFactory $services) {
        $this->configData = new ActiveCampaignConfigurations();

        parent::__construct($services);
    }

    /**
     * @param servicesFactory $services
     * @return ActiveCampaign
     */
    public function create(servicesFactory $services): ActiveCampaign {
        return new ActiveCampaign($this->configData, $services);
    }
}