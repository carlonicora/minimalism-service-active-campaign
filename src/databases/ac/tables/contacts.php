<?php
namespace carlonicora\minimalism\services\activeCampaign\databases\ac\tables;

use carlonicora\minimalism\services\MySQL\abstracts\abstractDatabaseManager;
use carlonicora\minimalism\services\MySQL\exceptions\dbRecordNotFoundException;

class contacts extends abstractDatabaseManager {
    /** @var array  */
    protected array $fields = [
        'userId' => self::INTEGER + self::PRIMARY_KEY,
        'contactId' => self::INTEGER + self::PRIMARY_KEY
    ];

    /**
     * @param int $userId
     * @return array
     * @throws dbRecordNotFoundException
     */
    public function userId(int $userId) : array {
        $sql = 'SELECT * FROM contacts WHERE userId=?;';
        $parameters = ['i', $userId];

        return $this->runReadSingle($sql, $parameters);
    }
}