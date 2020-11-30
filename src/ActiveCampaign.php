<?php
namespace CarloNicora\Minimalism\Services\ActiveCampaign;

use carlonicora\ActiveCampaign\Contacts\Contacts;
use CarloNicora\Minimalism\Core\Services\Abstracts\AbstractService;
use CarloNicora\Minimalism\Core\Services\Exceptions\ConfigurationException;
use CarloNicora\Minimalism\Core\Services\Exceptions\ServiceNotFoundException;
use CarloNicora\Minimalism\Core\Services\Factories\ServicesFactory;
use CarloNicora\Minimalism\Core\Services\Interfaces\ServiceConfigurationsInterface;
use CarloNicora\Minimalism\Services\ActiveCampaign\Databases\Ac\Tables\ContactsTable;
use CarloNicora\Minimalism\Services\ActiveCampaign\Configurations\ActiveCampaignConfigurations;
use CarloNicora\Minimalism\Services\MySQL\Exceptions\DbRecordNotFoundException;
use CarloNicora\Minimalism\Services\MySQL\Exceptions\DbSqlException;
use CarloNicora\Minimalism\Services\MySQL\MySQL;
use Exception;
use carlonicora\ActiveCampaign\Client;
use carlonicora\ActiveCampaign\Tags\Tags;
use JsonException;
use RuntimeException;

class ActiveCampaign extends AbstractService {
    /** @var ActiveCampaignConfigurations */
    private ActiveCampaignConfigurations $configData;

    /** @var ContactsTable|null  */
    private ?ContactsTable $contacts=null;

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
     * @return ContactsTable
     * @throws Exception
     */
    private function contacts() : ContactsTable {
        if ($this->contacts === null){
            /** @var MySQL $mysql */
            $mysql = $this->services->service(MySQL::class);

            /** @var ContactsTable $contacts */
            $contacts = $mysql->create(ContactsTable::class);

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
     * @return Contacts
     */
    private function activeCampaignContacts() : Contacts {
        return new Contacts($this->activeCampaignClient());
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
     * @return void
     * @throws dbSqlException
     * @throws Exception
     */
    public function subscribe(int $userId, string $email) : void {
        $activeCampaignContacts = $this->activeCampaignContacts();

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

        $contact = [
            'userId' => $userId,
            'contactId' => $contactId
        ];

        $this->contacts()->update($contact);
    }

    /**
     * @param int $userId
     * @throws Exception
     */
    public function unsubscribe(int $userId) : void {
        $contact = $this->contacts()->userId($userId);

        $activeCampaignContacts = $this->activeCampaignContacts();

        $activeCampaignContacts->updateListStatus([
            'list' => $this->configData->listId,
            'contact' => $contact['contactId'],
            'status' => 2
        ]);
    }

    /**
     * @param int $userId
     * @param string $tag
     * @throws Exception
     */
    public function addTag(int $userId, string $tag) : void{
        $tag = strtolower($tag);

        $contact = $this->contacts()->userId($userId);

        if (($tagId = $this->findTagId($tag) ?? $this->createTag($tag)) === null) {
            throw new RuntimeException('Tag not found', 404);
        }

        $activeCampaignContacts = $this->activeCampaignContacts();

        $activeCampaignContacts->tag($contact['contactId'], $tagId);
    }

    /**
     * @param int $userId
     * @param string $tag
     * @throws JsonException
     * @throws configurationException|dbRecordNotFoundException|serviceNotFoundException|dbSqlException
     * @throws Exception
     */
    public function removeTag(int $userId, string $tag): void {
        $tag = strtolower($tag);

        if (($tagId = $this->findTagId($tag)) === null) {
            throw new RuntimeException('Tag not found', 404);
        }

        $contact = $this->contacts()->userId($userId);

        $activeCampaignContacts = $this->activeCampaignContacts();

        $retrievedTagsJson = $activeCampaignContacts->getTags($contact['contactId']);
        $retrievedTags = json_decode($retrievedTagsJson, true, 512, JSON_THROW_ON_ERROR);

        $contactTagId = null;

        foreach ($retrievedTags['contactTags'] ?? [] as $contactTag){
            if ($contactTag['tag'] === $tagId){
                $contactTagId = $contactTag['id'];
                break;
            }
        }

        if ($contactTagId === null){
            return;
        }

        $activeCampaignContacts->untag($contactTagId);
    }

    /**
     * @param string $tag
     * @return int|null
     * @throws JsonException
     * @noinspection PhpDocRedundantThrowsInspection
     */
    public function findTagId(string $tag) : ?int {
        $tag = strtolower($tag);
        $activeCampaignTags = $this->activeCampaignTags();

        $jsonTags = $activeCampaignTags->listAll([$tag]);
        $tagsArray = json_decode($jsonTags, true, 512, JSON_THROW_ON_ERROR);$tagId = null;

        foreach ($tagsArray['tags'] as $tagArray){
            if ($tagArray['tag'] === $tag){
                return $tagArray['id'];
            }
        }

        return null;
    }

    /**
     * @param string $tag
     * @return int
     * @throws JsonException
     * @noinspection PhpDocRedundantThrowsInspection
     */
    public function createTag(string $tag) : int {
        $tag = strtolower($tag);

        $activeCampaignTags = $this->activeCampaignTags();

        $jsonTags = $activeCampaignTags->create($tag);
        $newTag = json_decode($jsonTags, true, 512, JSON_THROW_ON_ERROR);

        return $newTag['tag']['id'];
    }

    /**
     * @inheritDoc
     */
    public function destroyStatics(): void
    {
        $this->client = null;
    }
}