<?php
namespace CarloNicora\Minimalism\Services\ActiveCampaign\Databases\Ac\Tables;

use CarloNicora\Minimalism\Services\MySQL\Abstracts\AbstractTable;
use CarloNicora\Minimalism\Services\MySQL\Exceptions\DbRecordNotFoundException;
use CarloNicora\Minimalism\Services\MySQL\Exceptions\DbSqlException;

class ContactsTable extends AbstractTable {
    /** @var array  */
    protected array $fields = [
        'userId' => self::INTEGER + self::PRIMARY_KEY,
        'contactId' => self::INTEGER + self::PRIMARY_KEY
    ];

    /**
     * @param int $userId
     * @return array
     * @throws DbRecordNotFoundException
     * @throws DbSqlException
     */
    public function userId(int $userId) : array {
        $this->sql = 'SELECT * FROM contacts WHERE userId=?;';
        $this->parameters = ['i', $userId];

        return $this->functions->runReadSingle();
    }

    /**
     * @param int $contactId
     * @return array
     * @throws DbRecordNotFoundException
     * @throws DbSqlException
     */
    public function contactId(int $contactId) : array {
        $this->sql = 'SELECT * FROM contacts WHERE contactId=?;';
        $this->parameters = ['i', $contactId];

        return $this->functions->runReadSingle();
    }
}