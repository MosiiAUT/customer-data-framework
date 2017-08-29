<?php

/**
 * Pimcore Customer Management Framework Bundle
 * Full copyright and license information is available in
 * License.md which is distributed with this source code.
 *
 * @copyright  Copyright (C) Elements.at New Media Solutions GmbH
 * @license    GPLv3
 */

namespace CustomerManagementFrameworkBundle\SegmentBuilder;

use CustomerManagementFrameworkBundle\Model\CustomerInterface;
use CustomerManagementFrameworkBundle\SegmentManager\SegmentManagerInterface;


class GenderSegmentBuilder extends AbstractSegmentBuilder
{
    const MALE = 'male';
    const FEMALE = 'female';
    const NOT_SET = 'not-set';


    private $maleSegment;
    private $femaleSegment;
    private $notsetSegment;
    private $segmentGroup;

    private $segmentGroupName;
    private $maleSegmentName;
    private $femaleSegmentName;
    private $notsetSegmentName;
    private $valueMapping;

    public function __construct(
        $segmentGroupName = 'Gender',
        $maleSegmentName = self::MALE,
        $femaleSegmentName = self::FEMALE,
        $notsetSegmentName = self::NOT_SET,
        $valueMapping = []
    )
    {
        $this->segmentGroupName = $segmentGroupName;
        $this->maleSegmentName = $maleSegmentName;
        $this->femaleSegmentName = $femaleSegmentName;
        $this->notsetSegmentName = $notsetSegmentName;
        $this->valueMapping = sizeof($valueMapping) ? $valueMapping : [
            'male' => \CustomerManagementFrameworkBundle\SegmentBuilder\GenderSegmentBuilder::MALE,
            'female' =>\CustomerManagementFrameworkBundle\SegmentBuilder\GenderSegmentBuilder::FEMALE,
        ];
    }

    /**
     * prepare data and configurations which could be reused for all calculateSegments() calls
     *
     * @param SegmentManagerInterface $segmentManager
     *
     * @return void
     */
    public function prepare(SegmentManagerInterface $segmentManager)
    {
        $segmentGroupName = $this->segmentGroupName;

        $this->maleSegment = $segmentManager->createCalculatedSegment(
            $this->maleSegmentName,
            $segmentGroupName
        );
        $this->femaleSegment = $segmentManager->createCalculatedSegment(
            $this->femaleSegmentName,
            $segmentGroupName
        );
        $this->notsetSegment = $segmentManager->createCalculatedSegment(
            $this->notsetSegmentName,
            $segmentGroupName
        );

        $this->segmentGroup = $this->maleSegment->getGroup();
    }

    /**
     * build segment(s) for given customer
     *
     * @param CustomerInterface $customer
     * @param SegmentManagerInterface $segmentManager
     *
     * @return void
     */
    public function calculateSegments(CustomerInterface $customer, SegmentManagerInterface $segmentManager)
    {
        $valueMapping = $this->valueMapping;
        $gender = $valueMapping[$customer->getGender()] ?: self::NOT_SET;

        if ($gender == self::MALE) {
            $segment = $this->maleSegment;
        } elseif ($gender == self::FEMALE) {
            $segment = $this->femaleSegment;
        } else {
            $segment = $this->notsetSegment;
        }

        $segmentManager->mergeSegments(
            $customer,
            [$segment],
            $segmentManager->getSegmentsFromSegmentGroup($this->segmentGroup, [$segment]),
            'GenderSegmentBuilder'
        );
    }

    /**
     * return the name of the segment builder
     *
     * @return string
     */
    public function getName()
    {
        return 'GenderSegmentBuilder';
    }

    public function executeOnCustomerSave()
    {
        return true;
    }
}
