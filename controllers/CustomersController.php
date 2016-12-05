<?php

use BackendToolkit\Controller\Traits\PaginatorController;
use BackendToolkit\Listing\Filter;
use BackendToolkit\Listing\FilterHandler;
use CustomerManagementFramework\Controller\Admin;
use CustomerManagementFramework\Factory;
use CustomerManagementFramework\CustomerList\Filter\CustomerSegment as CustomerSegmentFilter;
use CustomerManagementFramework\Model\CustomerInterface;
use CustomerManagementFramework\Model\CustomerSegmentInterface;
use CustomerManagementFramework\Plugin;
use Pimcore\Model\Object\CustomerSegment;
use Pimcore\Model\Object\Listing;

class CustomerManagementFramework_CustomersController extends Admin
{
    use PaginatorController;

    public function init()
    {
        parent::init();
        $this->checkPermission('plugin_customermanagementframework_customerview');
    }

    public function listAction()
    {
        $this->enableLayout();

        $this->loadSegmentGroups();

        $filters   = $this->fetchListFilters();
        $listing   = $this->buildListing($filters);
        $paginator = $this->buildPaginator($listing);

        $this->view->paginator    = $paginator;
        $this->view->customerView = Factory::getInstance()->getCustomerView();
    }

    public function exportAction()
    {
        $this->disableLayout();
        $this->disableViewAutoRender();

        $exporterName    = $this->getParam('exporter', 'csv');
        $exporterManager = Factory::getInstance()->getCustomerListExporterManager();

        if (!$exporterManager->hasExporter($exporterName)) {
            throw new InvalidArgumentException('Exporter does not exist');
        }

        $filters  = $this->fetchListFilters();
        $listing  = $this->buildListing($filters);
        $exporter = $exporterManager->buildExporter($exporterName, $listing);

        $filename = sprintf(
            '%s-%s-segment-export.csv',
            $exporterName,
            \Carbon\Carbon::now()->format('YmdHis')
        );

        /** @var Zend_Controller_Response_Http $response */
        $response = $this->getResponse();
        $response
            ->setHeader('Content-Type', $exporter->getMimeType())
            ->setHeader('Content-Length', $exporter->getFilesize())
            ->setHeader('Content-Disposition', sprintf('attachment; filename="%s"', $filename))
            ->setBody($exporter->getExportData());
    }

    public function detailAction()
    {
        $this->enableLayout();

        $customer = Factory::getInstance()->getCustomerProvider()->getById((int)$this->getParam('id'));
        if ($customer && $customer instanceof CustomerInterface) {
            $customerView = Factory::getInstance()->getCustomerView();
            if (!$customerView->hasDetailView($customer)) {
                throw new RuntimeException(sprintf('Customer %d has no detail view to show', $customer->getId()));
            }

            $this->view->customer     = $customer;
            $this->view->customerView = $customerView;
        } else {
            throw new InvalidArgumentException('Invalid customer');
        }
    }

    /**
     * Load all segment groups
     */
    protected function loadSegmentGroups()
    {
        // TODO apply showAsFilter condition directly on segment manager
        $segmentGroups = Factory::getInstance()->getSegmentManager()->getSegmentGroups([]);
        $this->view->segmentGroups = [];

        /** @var \Pimcore\Model\Object\CustomerSegmentGroup $segmentGroup */
        foreach ($segmentGroups as $segmentGroup) {
            if ($segmentGroup->getShowAsFilter()) {
                $this->view->segmentGroups[] = $segmentGroup;
            }
        }
    }

    /**
     * @param array $filters
     * @return Listing\Concrete
     */
    protected function buildListing(array $filters = [])
    {
        $listing = Factory::getInstance()->getCustomerProvider()->getList();
        $listing
            ->setOrderKey('o_id')
            ->setOrder('ASC');

        $this->addListingFilters($listing, $filters);

        return $listing;
    }

    /**
     * @param Listing\Concrete $listing
     * @param array $filters
     */
    protected function addListingFilters(Listing\Concrete $listing, array $filters = [])
    {
        $handler = new FilterHandler($listing);

        $filterProperties = Plugin::getConfig()->CustomerList->filterProperties;

        $equalsProperties = isset($filterProperties->equals) ? $filterProperties->equals->toArray() : [];
        $searchProperties = isset($filterProperties->search) ? $filterProperties->search->toArray() : [];

        foreach ($equalsProperties as $property => $databaseField) {
            if (array_key_exists($property, $filters)) {
                $handler->addFilter(new Filter\Equals($databaseField, $filters[$property]));
            }
        }

        foreach ($searchProperties as $property => $databaseField) {
            if (array_key_exists($property, $filters)) {
                $handler->addFilter(new Filter\Search($databaseField, $filters[$property]));
            }
        }

        if (array_key_exists('segments', $filters)) {
            foreach ($filters['segments'] as $groupId => $segmentIds) {
                /** @var \Pimcore\Model\Object\CustomerSegmentGroup $segmentGroup */
                $segmentGroup = \Pimcore\Model\Object\CustomerSegmentGroup::getById($groupId);
                if (!$segmentGroup) {
                    throw new InvalidArgumentException(sprintf('Segment group %d was not found', $groupId));
                }

                $segments = [];
                foreach ($segmentIds as $segmentId) {
                    $segment = CustomerSegment::getById($segmentId);

                    if (!$segment) {
                        throw new InvalidArgumentException(sprintf('Segment %d was not found', $segmentId));
                    }

                    $segments[] = $segment;
                }

                $handler->addFilter(new CustomerSegmentFilter($segmentGroup, $segments));
            }
        }
    }

    /**
     * Fetch filters and set them on view
     *
     * @return array
     */
    protected function fetchListFilters()
    {
        /** @var \Zend_Controller_Action $this */
        $filters = $this->getParam('filter', []);
        $filters = $this->addPrefilteredSegmentToFilters($filters);

        $this->view->filters = $filters;

        return $filters;
    }

    /**
     * @param array $filters
     * @return array
     */
    protected function addPrefilteredSegmentToFilters(array $filters)
    {
        $segment = $this->fetchPrefilteredSegment();
        if ($segment) {
            if (!isset($filters['segments'])) {
                $filters['segments'] = [];
            }

            $groupSegmentIds = [];
            if (isset($filters['segments'][$segment->getGroup()->getId()])) {
                $groupSegmentIds = $filters['segments'][$segment->getGroup()->getId()];
            }

            if (!in_array($segment->getId(), $groupSegmentIds)) {
                $groupSegmentIds[] = $segment->getId();
            }

            $filters['segments'][$segment->getGroup()->getId()] = $groupSegmentIds;
        }

        return $filters;
    }

    /**
     * @return CustomerSegmentInterface|null
     */
    protected function fetchPrefilteredSegment()
    {
        $segmentId = $this->getParam('segmentId');

        if ($segmentId) {
            $segment = CustomerSegment::getById($segmentId);
            if (!$segment) {
                throw new InvalidArgumentException(sprintf('Segment %d was not found', $segmentId));
            }

            // params still needed when clearing all filters
            $clearUrlParams = $this->view->clearUrlParams ?: [];
            $clearUrlParams['segmentId'] = $segment->getId();

            $this->view->clearUrlParams  = $clearUrlParams;

            return $segment;
        }
    }
}
