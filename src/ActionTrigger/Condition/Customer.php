<?php
/**
 * Created by PhpStorm.
 * User: mmoser
 * Date: 07.12.2016
 * Time: 15:34
 */

namespace CustomerManagementFrameworkBundle\ActionTrigger\Condition;

use CustomerManagementFrameworkBundle\Model\CustomerInterface;
use Pimcore\Model\Object\AbstractObject;

class Customer extends AbstractCondition
{
    const OPTION_CUSTOMER_ID = 'customerId';
    const OPTION_CUSTOMER = 'customer';
    const OPTION_NOT = 'not';

    public function check(ConditionDefinitionInterface $conditionDefinition, CustomerInterface $customer)
    {
        $options = $conditionDefinition->getOptions();

        if (isset($options[self::OPTION_CUSTOMER_ID])) {
            if ($desiredCustomer = AbstractObject::getById(intval($options[self::OPTION_CUSTOMER_ID]))) {
                $check = $desiredCustomer->getId() == $customer->getId();

                if ($options[self::OPTION_NOT]) {
                    return !$check;
                }

                return $check;
            }
        }

        return false;
    }

    public function getDbCondition(ConditionDefinitionInterface $conditionDefinition)
    {
        $options = $conditionDefinition->getOptions();

        if (!$options[self::OPTION_CUSTOMER_ID]) {
            return '-1';
        }

        $customerId = intval($options[self::OPTION_CUSTOMER_ID]);

        $condition = sprintf('o_id = %s', $customerId);

        $not = $options[self::OPTION_NOT];

        if ($not) {
            $condition = '!('.$condition.')';
        }

        return $condition;
    }

    public static function createConditionDefinitionFromEditmode($setting)
    {
        $condition = parent::createConditionDefinitionFromEditmode($setting);

        $options = $condition->getOptions();

        if (isset($options[self::OPTION_CUSTOMER])) {
            $customer = AbstractObject::getByPath($options[self::OPTION_CUSTOMER]);
            $options[self::OPTION_CUSTOMER_ID] = $customer->getId();
            unset($options[self::OPTION_CUSTOMER]);
        }
        $condition->setOptions($options);

        return $condition;
    }

    public static function getDataForEditmode(ConditionDefinitionInterface $conditionDefinition)
    {
        $options = $conditionDefinition->getOptions();

        if (isset($options[self::OPTION_CUSTOMER_ID])) {
            if ($segment = AbstractObject::getById(intval($options[self::OPTION_CUSTOMER_ID]))) {
                $options[self::OPTION_CUSTOMER] = $segment->getFullPath();
            }
        }

        $conditionDefinition->setOptions($options);

        return $conditionDefinition->toArray();
    }
}
