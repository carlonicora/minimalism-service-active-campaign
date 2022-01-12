<?php
namespace CarloNicora\Minimalism\Services\ActiveCampaign;

use CarloNicora\Minimalism\Abstracts\AbstractService;
use Exception;
use TestMonitor\ActiveCampaign\Resources\Contact;

class ActiveCampaign extends AbstractService
{
    /** @var \TestMonitor\ActiveCampaign\ActiveCampaign|null */
    private ?\TestMonitor\ActiveCampaign\ActiveCampaign $client = null;

    /**
     * ActiveCampaign constructor.
     * @param string $MINIMALISM_SERVICE_ACTIVECAMPAIGN_URL
     * @param string $MINIMALISM_SERVICE_ACTIVECAMPAIGN_KEY
     * @param string $MINIMALISM_SERVICE_ACTIVECAMPAIGN_LISTID
     */
    public function __construct(
        private string $MINIMALISM_SERVICE_ACTIVECAMPAIGN_URL,
        private string $MINIMALISM_SERVICE_ACTIVECAMPAIGN_KEY,
        private string $MINIMALISM_SERVICE_ACTIVECAMPAIGN_LISTID,
    )
    {
        parent::__construct();
    }

    /**
     * @return \TestMonitor\ActiveCampaign\ActiveCampaign
     */
    private function activeCampaignClient(): \TestMonitor\ActiveCampaign\ActiveCampaign
    {
        if ($this->client === null) {
            $this->client = new \TestMonitor\ActiveCampaign\ActiveCampaign(
                $this->MINIMALISM_SERVICE_ACTIVECAMPAIGN_URL,
                $this->MINIMALISM_SERVICE_ACTIVECAMPAIGN_KEY,
            );
        }

        return $this->client;
    }

    /**
     * @param string $email
     * @return void
     * @throws Exception
     */
    public function subscribe(string $email): void
    {
        $activeCampaignList = $this->activeCampaignClient()
            ->getList($this->MINIMALISM_SERVICE_ACTIVECAMPAIGN_LISTID);

        if ($activeCampaignList !== null) {
            $this->getContact($email)->subscribe(
                $activeCampaignList->id
            );
        }
    }

    /**
     * @param string $email
     * @throws Exception
     */
    public function unsubscribe(string $email): void
    {
        $activeCampaignList = $this->activeCampaignClient()
            ->getList($this->MINIMALISM_SERVICE_ACTIVECAMPAIGN_LISTID);

        if ($activeCampaignList !== null) {
            $this->getContact($email)->unsubscribe(
                $activeCampaignList->id
            );
        }
    }

    /**
     * @param string $email
     * @param string $tag
     * @throws Exception
     */
    public function addTag(string $email, string $tag): void
    {
        $this->activeCampaignClient()->addTagsToContact(
            $this->getContact($email),
            [strtolower($tag)]
        );
    }

    /**
     * @param string $email
     * @param string $tag
     * @throws Exception
     */
    public function removeTag(string $email, string $tag): void
    {
        $activeCampaignTag = $this->activeCampaignClient()
            ->findOrCreateTag(strtolower($tag));

        $this->activeCampaignClient()->removeTagFromContact(
            $this->getContact($email),
            $activeCampaignTag
        );
    }

    /**
     * @param string $email
     * @return Contact
     */
    private function getContact(string $email): Contact
    {
        return $this->activeCampaignClient()
            ->findOrCreateContact(
                $email,
                '',
                '',
                ''
            );
    }

}