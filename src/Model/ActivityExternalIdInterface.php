<?php

namespace CustomerManagementFrameworkBundle\Model;

interface ActivityExternalIdInterface extends ActivityInterface
{
    /**
     * Returns external ID of the activity. Needed in order to be able to update the entry in the activity store based on this ID.
     *
     * @return string/int
     */
    public function getId();
}
