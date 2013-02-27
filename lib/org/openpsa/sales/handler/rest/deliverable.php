<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 * @author Jan Floegel
 * @package midcom.baseclasses
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General
 */

/**
 * org.openpsa.sales deliverable rest handler
 *
 * @package org.openpsa.sales
 */
class org_openpsa_sales_handler_rest_deliverable extends midcom_baseclasses_components_handler_rest
{

    public function get_object_classname()
    {
        return "org_openpsa_sales_salesproject_deliverable_dba";
    }
    
    public function handle_update()
    {    
        // if endtime was set, we need to set continuous to false
        if (isset($this->_request['params']['end']))
        {
            $this->_request['params']['continuous'] = false;
        }
                
        parent::handle_update();
    }
}
?>