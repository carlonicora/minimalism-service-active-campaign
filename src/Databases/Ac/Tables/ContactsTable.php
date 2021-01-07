<?php
namespace CarloNicora\Minimalism\Services\ActiveCampaign\Databases\Ac\Tables;

use CarloNicora\Minimalism\Exceptions\RecordNotFoundException;
use CarloNicora\Minimalism\Services\MySQL\Abstracts\AbstractMySqlTable;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\FieldInterface;

class ContactsTable extends AbstractMySqlTable
{
    /** @var array  */
    protected array $fields = [
        'userId'    => FieldInterface::INTEGER
                    +  FieldInterface::PRIMARY_KEY,
        'contactId' => FieldInterface::INTEGER
                    +  FieldInterface::PRIMARY_KEY
    ];

    /**
     * @param int $userId
     * @return array
     * @throws RecordNotFoundException
     */
    public function userId(int $userId) : array {
        $this->sql = 'SELECT * FROM contacts WHERE userId=?;';
        $this->parameters = ['i', $userId];

        return $this->functions->runReadSingle();
    }

    /**
     * @param int $contactId
     * @return array
     * @throws RecordNotFoundException
     */
    public function contactId(int $contactId) : array {
        $this->sql = 'SELECT * FROM contacts WHERE contactId=?;';
        $this->parameters = ['i', $contactId];

        return $this->functions->runReadSingle();
    }
}