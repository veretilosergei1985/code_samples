<?php

namespace App\ZendeskBundle\Service;

use Chernoff\Datatable\DatatableInterface;
use InvalidArgumentException;

class DataTableRegistry
{
    /**
     * @var array
     */
    private $dataTables = [];


    public function add(DatatableInterface $datatable)
    {
        $this->dataTables[$datatable->getName()] = $datatable;
    }

    /**
     * @return array
     */
    public function getDataTables()
    {
        return $this->dataTables;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasDataTable(string $name): bool
    {
        return isset($this->dataTables[$name]);
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getDataTable(string $name): DatatableInterface
    {
        if (!$this->hasDataTable($name)) {
            throw new InvalidArgumentException(sprintf('DataTable "%s" does not exist.', $name));
        }

        return $this->dataTables[$name];
    }
}
