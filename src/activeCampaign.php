<?php
namespace carlonicora\minimalism\services\activeCampaign;

use carlonicora\minimalism\core\services\abstracts\abstractService;
use carlonicora\minimalism\core\services\exceptions\serviceNotFoundException;
use carlonicora\minimalism\core\services\factories\servicesFactory;
use carlonicora\minimalism\core\services\interfaces\serviceConfigurationsInterface;
use carlonicora\minimalism\services\activeCampaign\configurations\activeCampaignConfigurations;
use carlonicora\minimalism\services\activeCampaign\databases\ac\tables\contacts;
use carlonicora\minimalism\services\MySQL\exceptions\dbConnectionException;
use carlonicora\minimalism\services\MySQL\exceptions\dbSqlException;
use carlonicora\minimalism\services\MySQL\exceptions\dbUpdateException;
use carlonicora\minimalism\services\MySQL\MySQL;
use Exception;
use carlonicora\ActiveCampaign\Client;
use carlonicora\ActiveCampaign\Tags\Tags;
use RuntimeException;

class activeCampaign extends abstractService {
    /** @var activeCampaignConfigurations  */
    private activeCampaignConfigurations $configData;

    /** @var contacts|null  */
    private ?contacts $contacts=null;

    /** @var Client|null  */
    private ?Client $client=null;

    /**
     * activeCampaign constructor.
     * @param serviceConfigurationsInterface $configData
     * @param servicesFactory $services
     */
    public function __construct(serviceConfigurationsInterface $configData, servicesFactory $services) {
        parent::__construct($configData, $services);

        /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $this->configData = $configData;
    }

    /**
     * @return contacts
     * @throws serviceNotFoundException
     * @throws dbConnectionException
     */
    private function contacts() : contacts {
        if ($this->contacts === null){
            /** @var MySQL $mysql */
            $mysql = $this->services->service(MySQL::class);

            /** @var contacts $contacts */
            $contacts = $mysql->create(contacts::class);

            $this->contacts = $contacts;
        }

        return $this->contacts;
    }

    /**
     * @return Client
     */
    private function activeCampaignClient(): Client {
        if ($this->client === null) {
            $this->client = new Client($this->configData->url, $this->configData->key);
        }

        return $this->client;
    }

    /**
     * @return \carlonicora\ActiveCampaign\Contacts\Contacts
     */
    private function activeCampaignContacts() : \carlonicora\ActiveCampaign\Contacts\Contacts {
        return new \carlonicora\ActiveCampaign\Contacts\Contacts($this->activeCampaignClient());
    }

    /**
     * @return Tags
     */
    private function activeCampaignTags() : Tags {
        return new Tags($this->activeCampaignClient());
    }

    /**
     * @param int $userId
     * @param string $email
     * @param string $unsubscribeLink
     * @return void
     * @throws dbSqlException
     * @throws dbUpdateException
     * @throws Exception
     */
    public function subscribe(int $userId, string $email, string $unsubscribeLink) : void {
        $activeCampaignContacts = $this->activeCampaignContacts();

        try {
            $retrievedContactJson = $activeCampaignContacts->sync([
                'email' => $email
            ]);

            $retrievedContact = json_decode($retrievedContactJson, true, 512, JSON_THROW_ON_ERROR);
            $contactId = $retrievedContact['contact']['id'];

            $activeCampaignContacts->updateListStatus([
                'list' => $this->configData->listId,
                'contact' => $contactId,
                'status' => 1
            ]);

            $activeCampaignContacts->createCustomFieldValue(
                $contactId,
                $this->configData->unsubscribeField,
                $unsubscribeLink
            );
        } catch (Exception $e) {
            throw new RuntimeException('Error contacting the mail service', $e->getCode());
        }

        $contact = [
            'userId' => $userId,
            'contactId' => $contactId
        ];

        $this->contacts->update($contact);
    }

    /**
     * @param int $userId
     * @throws Exception
     */
    public function unsubscribe(int $userId) : void {
        $contact = $this->contacts()->userId($userId);

        $activeCampaignContacts = $this->activeCampaignContacts();

        try {
            $activeCampaignContacts->updateListStatus([
                'list' => $this->configData->listId,
                'contact' => $contact['contactId'],
                'status' => 2
            ]);
        } catch (Exception $e) {
            throw new RuntimeException('Error contacting the mail service', $e->getCode());
        }
    }

    /**
     * @param int $userId
     * @param string $tag
     * @throws Exception
     */
    public function addTag(int $userId, string $tag) : void{
        $contact = $this->contacts()->userId($userId);

        $activeCampaignTags = $this->activeCampaignTags();
        $jsonTags = $activeCampaignTags->listAll([$tag]);
        $tagsArray = json_decode($jsonTags, true, 512, JSON_THROW_ON_ERROR);

        $tagId = null;
        foreach ($tagsArray['tags'] as $tagArray){
            if ($tagArray['tag'] === $tag){
                $tagId = $tagArray['id'];
                break;
            }
        }

        if ($tagId === null) {
            throw new RuntimeException('Tag not found', 404);
        }

        $activeCampaignContacts = $this->activeCampaignContacts();

        try {
            $activeCampaignContacts->tag($contact['contactId'], $tagId);
        } catch (Exception $e) {
            throw new RuntimeException('Error contacting the mail service', $e->getCode());
        }
    }
}