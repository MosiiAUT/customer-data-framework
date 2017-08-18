<?php

namespace CustomerManagementFrameworkBundle\Templating\Helper;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Templating\Helper\Helper;

class EscapeFormValue extends Helper
{
    public function getName()
    {
        return 'escapeFormValue';
    }

    /**
     * Returns the helper instance. If a request is given, call formQueryString() directly.
     *
     * @param string|null $varName
     *
     * @return string|self
     */
    public function __invoke($value = null)
    {
        return $this->escapeFormValue($value);
    }

    /**
     * Escapes a value for outputting as a html value attribute
     *
     * @param Request $request
     * @param $url
     * @param bool $includeOrder
     * @param bool $includeFilters
     *
     * @return string
     */
    public function escapeFormValue($value)
    {
        $engine = \Pimcore::getContainer()->get('pimcore.templating.engine.php');

        return str_replace('"', '\"', $engine->escape($value));
    }
}
