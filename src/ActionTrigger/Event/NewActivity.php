<?php
/**
 * Created by PhpStorm.
 * User: mmoser
 * Date: 22.11.2016
 * Time: 12:33
 */

namespace CustomerManagementFrameworkBundle\ActionTrigger\Event;

use CustomerManagementFrameworkBundle\ActionTrigger\Trigger\TriggerDefinitionInterface;
use CustomerManagementFrameworkBundle\Model\ActivityInterface;
use CustomerManagementFrameworkBundle\Model\ActivityStoreEntry\ActivityStoreEntryInterface;

class NewActivity extends AbstractSingleCustomerEvent
{
    /**
     * @var ActivityInterface $activity
     */
    private $activity;

    /** @var ActivityStoreEntryInterface */
    private $entry;

    const OPTION_TYPE = 'type';

    /**
     * @return ActivityInterface
     */
    public function getActivity()
    {
        return $this->activity;
    }

    /**
     * @param ActivityInterface $activity
     */
    public function setActivity(ActivityInterface $activity)
    {
        $this->activity = $activity;
    }

    /**
     * @return ActivityStoreEntryInterface
     */
    public function getEntry()
    {
        return $this->entry;
    }

    /**
     * @param ActivityStoreEntryInterface $entry
     */
    public function setEntry($entry)
    {
        $this->entry = $entry;
    }

    public function getName()
    {
        return 'plugin.cmf.new-activity';
    }

    public function appliesToTrigger(TriggerDefinitionInterface $trigger)
    {
        if ($trigger->getEventName() != $this->getName()) {
            return false;
        }

        $options = $trigger->getOptions();

        if (!empty($options[self::OPTION_TYPE])) {
            if ($this->activity->cmfGetType() == $options[self::OPTION_TYPE]) {
                return true;
            }
        }

        return false;
    }
}
