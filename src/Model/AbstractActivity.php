<?php
/**
 * Created by PhpStorm.
 * User: mmoser
 * Date: 07.10.2016
 * Time: 13:37
 */

namespace CustomerManagementFrameworkBundle\Model;

use Carbon\Carbon;
use CustomerManagementFrameworkBundle\Model\ActivityStoreEntry\ActivityStoreEntryInterface;

abstract class AbstractActivity implements ActivityInterface
{
    protected $customer;

    /**
     * @return bool
     */
    public function cmfIsActive()
    {
        return true;
    }

    /**
     * @return Carbon
     */
    public function cmfGetActivityDate()
    {
        return new Carbon();
    }

    /**
     * @return bool
     */
    public function cmfUpdateOnSave()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function cmfWebserviceUpdateAllowed()
    {
        return !($this instanceof PersistentActivityInterface);
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    public function cmfUpdateData(array $data)
    {
        return false;
    }

    /**
     * @param array $data
     * @param bool $fromWebservice
     *
     * @return bool
     */
    public static function cmfCreate(array $data, $fromWebservice = false)
    {
        return false;
    }

    public static function cmfGetAttributeDataTypes()
    {
        return false;
    }

    /**
     * @param ActivityStoreEntryInterface $entry
     *
     * @return bool
     */
    public static function cmfGetOverviewData(ActivityStoreEntryInterface $entry)
    {
        return false;
    }

    /**
     * @param ActivityStoreEntryInterface $entry
     *
     * @return array
     */
    public static function cmfGetDetailviewData(ActivityStoreEntryInterface $entry)
    {
        return $entry->getAttributes();
    }

    /**
     * @param ActivityStoreEntryInterface $entry
     *
     * @return bool
     */
    public static function cmfGetDetailviewTemplate(ActivityStoreEntryInterface $entry)
    {
        return false;
    }

    /**
     * @return CustomerInterface
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * @param CustomerInterface $customer
     */
    public function setCustomer($customer)
    {
        $this->customer = $customer;
    }
}
