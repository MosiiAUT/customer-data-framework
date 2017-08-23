<?php

/**
 * Pimcore Customer Management Framework Bundle
 * Full copyright and license information is available in
 * License.md which is distributed with this source code.
 *
 * @copyright  Copyright (C) Elements.at New Media Solutions GmbH
 * @license    GPLv3
 */

namespace CustomerManagementFrameworkBundle\CustomerMerger;

use CustomerManagementFrameworkBundle\Config;
use CustomerManagementFrameworkBundle\CustomerSaveManager\CustomerSaveManagerInterface;
use CustomerManagementFrameworkBundle\Helper\Notes;
use CustomerManagementFrameworkBundle\Helper\Objects;
use CustomerManagementFrameworkBundle\Model\ActivityStoreEntry\ActivityStoreEntryInterface;
use CustomerManagementFrameworkBundle\Model\CustomerInterface;
use CustomerManagementFrameworkBundle\Traits\LoggerAware;
use Pimcore\Model\Object\ClassDefinition;
use Pimcore\Model\Object\Service;

class DefaultCustomerMerger implements CustomerMergerInterface
{
    use LoggerAware;

    protected $config;

    /**
     * @var CustomerSaveManagerInterface
     */
    protected $customerSaveManager;

    public function __construct(CustomerSaveManagerInterface $customerSaveManager)
    {
        $this->customerSaveManager = $customerSaveManager;

        $config = Config::getConfig();
        $this->config = $config->CustomerMerger;
    }

    /**
     * Adds all values from source customer to target customer and returns merged target customer instance.
     * Afterwards the source customer will be set to inactive and unpublished.
     *
     * @param CustomerInterface $sourceCustomer
     * @param CustomerInterface $targetCustomer
     * @param bool $mergeAttributes
     *
     * @return CustomerInterface
     */
    public function mergeCustomers(
        CustomerInterface $sourceCustomer,
        CustomerInterface $targetCustomer,
        $mergeAttributes = true
    ) {
        $saveValidatorBackup = $this->customerSaveManager->getCustomerSaveValidatorEnabled();
        $segmentBuilderHookBackup = $this->customerSaveManager->getSegmentBuildingHookEnabled();

        $this->customerSaveManager->setCustomerSaveValidatorEnabled(false);
        $this->customerSaveManager->setSegmentBuildingHookEnabled(false);

        $this->mergeCustomerValues($sourceCustomer, $targetCustomer, $mergeAttributes);
        $targetCustomer->save();

        if (!$sourceCustomer->getId()) {
            $note = Notes::createNote($targetCustomer, 'cmf.CustomerMerger', 'customer merged');
            $note->setDescription('merged with new customer instance');
            $note->save();
        } else {
            $note = Notes::createNote($targetCustomer, 'cmf.CustomerMerger', 'customer merged');
            $note->setDescription('merged with existing customer instance');
            $note->addData('mergedCustomer', 'object', $sourceCustomer);
            $note->save();

            $sourceCustomer->setParent(
                Service::createFolderByPath(
                    (string)$this->config->archiveDir ? (string)$this->config->archiveDir : '/customers/_archive'
                )
            );
            $sourceCustomer->setPublished(false);
            $sourceCustomer->setActive(false);
            $sourceCustomer->setKey($sourceCustomer->getId());
            $sourceCustomer->save();

            $note = Notes::createNote($sourceCustomer, 'cmf.CustomerMerger', 'customer merged + deactivated');
            $note->addData('mergedTargetCustomer', 'object', $targetCustomer);
            $note->save();
        }

        $this->customerSaveManager->setCustomerSaveValidatorEnabled( $saveValidatorBackup );
        $this->customerSaveManager->setSegmentBuildingHookEnabled( $segmentBuilderHookBackup );

        $logAddon = '';
        if (!$mergeAttributes) {
            $logAddon .= ' (attributes merged manually)';
        }

        $this->getLogger()->notice('merge customer '.$sourceCustomer.' with '.$targetCustomer.$logAddon);

        return $targetCustomer;
    }

    /**
     * @param CustomerInterface $sourceCustomer
     * @param CustomerInterface $targetCustomer
     * @param $mergeAttributes
     */
    private function mergeCustomerValues(
        CustomerInterface $sourceCustomer,
        CustomerInterface $targetCustomer,
        $mergeAttributes
    ) {
        if ($mergeAttributes) {
            $class = ClassDefinition::getById($sourceCustomer::classId());

            foreach ($class->getFieldDefinitions() as $fd) {
                $getter = 'get'.ucfirst($fd->getName());
                $setter = 'set'.ucfirst($fd->getName());

                if ($value = $sourceCustomer->$getter()) {
                    $targetCustomer->$setter($value);
                }
            }
        }

        $calculatedSegments = (array)$sourceCustomer->getCalculatedSegments();
        Objects::addObjectsToArray($calculatedSegments, (array)$targetCustomer->getCalculatedSegments());
        $targetCustomer->setCalculatedSegments($calculatedSegments);

        $manualSegments = (array)$sourceCustomer->getManualSegments();
        Objects::addObjectsToArray($manualSegments, (array)$targetCustomer->getManualSegments());
        $targetCustomer->setManualSegments($manualSegments);

        $this->customerSaveManager->setCustomerSaveValidatorEnabled(false);

        $this->mergeActivities($sourceCustomer, $targetCustomer);
    }

    /**
     * @param CustomerInterface $sourceCustomer
     * @param CustomerInterface $targetCustomer
     */
    private function mergeActivities(CustomerInterface $sourceCustomer, CustomerInterface $targetCustomer)
    {
        $list = \Pimcore::getContainer()->get('cmf.activity_store')->getActivityList();
        $list->setCondition('customerId='.$sourceCustomer->getId());
        $list->setOrderKey('activityDate');
        $list->setOrder('desc');

        /**
         * @var ActivityStoreEntryInterface $item
         */
        foreach ($list as $item) {
            $item->setCustomer($targetCustomer);
            $item->save();
        }
    }
}
