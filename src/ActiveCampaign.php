<?php
namespace CarloNicora\Minimalism\Services\ActiveCampaign;

use carlonicora\ActiveCampaign\Contacts\Contacts;
use CarloNicora\Minimalism\Interfaces\DataInterface;
use CarloNicora\Minimalism\Interfaces\ServiceInterface;
use CarloNicora\Minimalism\Interfaces\TableInterface;
use CarloNicora\Minimalism\Services\ActiveCampaign\Databases\Ac\Tables\ContactsTable;
use Exception;
use carlonicora\ActiveCampaign\Client;
use carlonicora\ActiveCampaign\Tags\Tags;
use JsonException;
use RuntimeException;

class ActiveCampaign implements ServiceInterface
{
    /** @var ContactsTable|null  */
    private ?ContactsTable $contacts=null;

    /** @var Client|null  */
    private ?Client $client=null;

    /**
     * ActiveCampaign constructor.
     * @param DataInterface $data
     * @param string $MINIMALISM_SERVICE_ACTIVECAMPAIGN_URL
     * @param string $MINIMALISM_SERVICE_ACTIVECAMPAIGN_KEY
     * @param string $MINIMALISM_SERVICE_ACTIVECAMPAIGN_LISTID
     * @param string $MINIMALISM_SERVICE_MYSQL
     */
    public function __construct(
        private DataInterface $data,
        private string $MINIMALISM_SERVICE_ACTIVECAMPAIGN_URL,
        private string $MINIMALISM_SERVICE_ACTIVECAMPAIGN_KEY,
        private string $MINIMALISM_SERVICE_ACTIVECAMPAIGN_LISTID,
        private string $MINIMALISM_SERVICE_MYSQL,
    ) {
    }

    /**
     * @return ContactsTable|TableInterface
     * @throws Exception
     */
    private function contacts() : ContactsTable|TableInterface
    {
        if ($this->contacts === null){
            /** @var ContactsTable|TableInterface $contacts */
            $contacts = $this->data->create(ContactsTable::class);

            $this->contacts = $contacts;
        }

        return $this->contacts;
    }

    /**
     * @return Client
     */
    private function activeCampaignClient(): Client {
        if ($this->client === null) {
            $this->client = new Client(
                $this->MINIMALISM_SERVICE_ACTIVECAMPAIGN_URL,
                $this->MINIMALISM_SERVICE_ACTIVECAMPAIGN_KEY,
            );
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
            'list' => $this->MINIMALISM_SERVICE_ACTIVECAMPAIGN_LISTID,
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
            'list' => $this->MINIMALISM_SERVICE_ACTIVECAMPAIGN_LISTID,
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
     *
     */
    public function initialise(): void {}

    /**
     *
     */
    public function destroy(): void {}
}