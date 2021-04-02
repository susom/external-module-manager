<?php


namespace Stanford\ExternalModuleManager;

/**
 * Class repository
 * @package Stanford\ExternalModuleManager
 * @property array
 */
class Repository
{

    private $record;

    public function __construct($record)
    {
        $this->setRecord($record);
    }

    /**
     * @return mixed
     */
    public function getRecord()
    {
        return $this->record;
    }

    /**
     * @param mixed $record
     */
    public function setRecord($record): void
    {
        $this->record = $record;
    }


}
