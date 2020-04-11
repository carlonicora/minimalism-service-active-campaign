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
use RuntimeException;

class activeCampaign extends abstractService {
    /** @var activeCampaignConfigurations  */
    private activeCampaignConfigurations $configData;

    /** @var contacts  */
    private contacts $contacts;

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
     * @param int $userId
     * @param string $unsubscribeLink
     * @param string $email
     * @return void
     * @throws dbSqlException
     * @throws dbUpdateException
     * @throws Exception
     */
    public function subscribe(int $userId, string $unsubscribeLink, string $email) : void {
        $activeContact = array(
            'email' => $email,
            'field[' .$this->configData->unsubscribeField.',0]' => $unsubscribeLink,
            "p[{$this->configData->listId}]" => $this->configData->listId,
            "status[{$this->configData->listId}]" => 1
        );

        $apiResponse = $this->sendRequest('contact_sync', $activeContact);

        if ($apiResponse['result_code'] === 0) {
            throw new RuntimeException($apiResponse['result_message']);
        }

        $contactId = (int)$apiResponse['subscriber_id'];

        $contact = [
            'userId' => $userId,
            'contactId' => $contactId
        ];

        $this->contacts->update($contact);
    }

    /**
     * @param int $userId
     * @param string $email
     * @throws Exception
     */
    public function unsubscribe(int $userId) : void {
        $contact = $this->contacts()->userId($userId);

        $activeContact = array(
            'id' => $contact['activeCampaignId'],
            "p[{$this->configData->listId}]" => $this->configData->listId,
            "status[{$this->configData->listId}]" => 2,
            'overwrite' => 0
        );

        $apiResponse = $this->sendRequest('contact_edit', $activeContact);

        if ($apiResponse['result_code'] === 0) {
            throw new RuntimeException($apiResponse['result_message']);
        }
    }

    /**
     * @param int $userId
     * @param string $tag
     * @throws Exception
     */
    public function addTag(int $userId, string $tag) : void{
        $contact = $this->contacts()->userId($userId);

        $activeContact = array(
            'id' => $contact['activeCampaignId'],
            'tag' => $tag
        );

        $apiResponse = $this->sendRequest('contact_tag_add', $activeContact);

        if ($apiResponse['result_code'] === 0) {
            throw new RuntimeException($apiResponse['result_message']);
        }
    }

    /**
     * @param string $action
     * @param array $post
     * @return array
     * @throws Exception
     */
    private function sendRequest(string $action, array $post) : array {
        $params = [
            'api_key' => $this->configData->key,
            'api_action'   => $action,
            'api_output'   => 'serialize'
        ];

        $query = '';
        foreach( $params as $key => $value ) {
            $query .= urlencode($key) . '=' . urlencode($value) . '&';
        }
        $query = rtrim($query, '& ');

        $data = '';
        foreach( $post as $key => $value ) {
            $data .= urlencode($key) . '=' . urlencode($value) . '&';
        }
        $data = rtrim($data, '& ');

        $url = rtrim($this->configData->url, '/ ');

        $api = $url . '/admin/api.php?' . $query;

        $request = curl_init($api);
        curl_setopt($request, CURLOPT_HEADER, 0);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($request, CURLOPT_POSTFIELDS, $data);
        curl_setopt($request, CURLOPT_FOLLOWLOCATION, true);

        $apiResponse = (string)curl_exec($request);

        curl_close($request); // close curl object

        if ( !$apiResponse ) {
            throw new RuntimeException('Error receiveing data', 500);
        }

        /** @noinspection UnserializeExploitsInspection */
        return unserialize($apiResponse);
    }
}