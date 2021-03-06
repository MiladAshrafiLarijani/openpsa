<?php
/**
 * Created on 2006-08-09
 * @author Henri Bergius
 * @package org.openpsa.products
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * @package org.openpsa.products
 */
class org_openpsa_products_handler_group_list  extends midcom_baseclasses_components_handler
{
    /**
     * @var midcom_helper_datamanager2_datamanager
     */
    private $datamanager;

    /**
     * @var midcom_helper_datamanager2_controller_ajax
     */
    private $controller;

    /**
     * The handler for the group_list article.
     *
     * @param mixed $handler_id the array key from the request array
     * @param array $args the arguments given to the handler
     * @param array &$data The local request data.
     */
    public function _handler_list($handler_id, array $args, array &$data)
    {
        $data['group'] = new org_openpsa_products_product_group_dba($args[0]);
        $this->datamanager = new midcom_helper_datamanager2_datamanager($data['schemadb_group']);
        $data['products'] = array();
        $this->_list_group_products();

        $this->_populate_toolbar();

        if (midcom::get()->config->get('enable_ajax_editing')) {
            $this->controller = midcom_helper_datamanager2_controller::create('ajax');
            $this->controller->schemadb =& $data['schemadb_group'];
            $this->controller->set_storage($data['group']);
            $this->controller->process_ajax();
            $this->datamanager = $this->controller->datamanager;
        } else {
            $this->controller = null;
            if (!$this->datamanager->autoset_storage($data['group'])) {
                throw new midcom_error("Failed to create a DM2 instance for product group {$data['group']->guid}.");
            }
        }
        $this->bind_view_to_object($data['group'], $this->datamanager->schema->name);

        $this->_update_breadcrumb_line();

        $data['view_title'] = $data['group']->title;
        if ($this->_config->get('code_in_title')) {
            $data['view_title'] = $data['group']->code . ' ' . $data['view_title'];
        }

        midcom::get()->head->set_pagetitle($data['view_title']);
        org_openpsa_widgets_grid::add_head_elements();
    }

    private function _populate_toolbar()
    {
        $workflow = $this->get_workflow('datamanager2');
        $this->_view_toolbar->add_item($workflow->get_button("edit/{$this->_request_data['group']->guid}/", array(
            MIDCOM_TOOLBAR_ENABLED => $this->_request_data['group']->can_do('midgard:update'),
            MIDCOM_TOOLBAR_ACCESSKEY => 'e',
        )));
        $allow_create_group = $this->_request_data['group']->can_do('midgard:create');
        $allow_create_product = $this->_request_data['group']->can_do('midgard:create');

        if ($this->_request_data['group']->orgOpenpsaObtype == org_openpsa_products_product_group_dba::TYPE_SMART) {
            $allow_create_product = false;
        }

        $this->_add_schema_buttons('schemadb_group', 'new-dir', '', $allow_create_group);
        $this->_add_schema_buttons('schemadb_product', 'new-text', 'product/', $allow_create_product);
    }

    private function _add_schema_buttons($schemadb_name, $default_icon, $prefix, $allowed)
    {
        $workflow = $this->get_workflow('datamanager2');
        foreach (array_keys($this->_request_data[$schemadb_name]) as $name) {
            $config = array(
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/' . $default_icon . '.png',
                MIDCOM_TOOLBAR_ENABLED => $allowed,
                MIDCOM_TOOLBAR_LABEL => sprintf(
                    $this->_l10n_midcom->get('create %s'),
                    $this->_l10n->get($this->_request_data[$schemadb_name][$name]->description)
                ),
            );
            if (isset($this->_request_data[$schemadb_name][$name]->customdata['icon'])) {
                $config[MIDCOM_TOOLBAR_ICON] = $this->_request_data[$schemadb_name][$name]->customdata['icon'];
            }
            $create_url = 'create/' . $this->_request_data['group']->id . '/' . $name . '/';
            $this->_view_toolbar->add_item($workflow->get_button($prefix . $create_url, $config));
        }
    }

    private function _list_group_products()
    {
        $product_qb = org_openpsa_products_product_dba::new_query_builder();

        if (   !empty($this->_request_data['group'])
            && $this->_request_data['group']->orgOpenpsaObtype == org_openpsa_products_product_group_dba::TYPE_SMART) {
            // Smart group, query products by stored constraints
            $constraints = $this->_request_data['group']->list_parameters('org.openpsa.products:constraints');
            if (empty($constraints)) {
                $product_qb->add_constraint('productGroup', '=', $this->_request_data['group']->id);
            }

            $reflector = new midgard_reflection_property('org_openpsa_products_product');

            foreach ($constraints as $constraint_string) {
                $constraint_members = explode(',', $constraint_string);
                if (count($constraint_members) != 3) {
                    throw new midcom_error("Invalid constraint '{$constraint_string}'");
                }

                // Reflection is needed here for safety
                $field_type = $reflector->get_midgard_type($constraint_members[0]);
                switch ($field_type) {
                    case 4:
                        throw new midcom_error("Invalid constraint: '{$constraint_members[0]}' is not a Midgard property");
                    case MGD_TYPE_INT:
                        $constraint_members[2] = (int) $constraint_members[2];
                        break;
                    case MGD_TYPE_FLOAT:
                        $constraint_members[2] = (float) $constraint_members[2];
                        break;
                    case MGD_TYPE_BOOLEAN:
                        $constraint_members[2] = (boolean) $constraint_members[2];
                        break;
                }
                $product_qb->add_constraint($constraint_members[0], $constraint_members[1], $constraint_members[2]);
            }
        } else {
            $product_qb->add_constraint('productGroup', '=', $this->_request_data['group']->id);
        }

        if ($this->_config->get('enable_scheduling')) {
            $product_qb->add_constraint('start', '<=', time());
            $product_qb->begin_group('OR');
            /*
             * List products that either have no defined end-of-market dates
             * or are still in market
             */
            $product_qb->add_constraint('end', '=', 0);
            $product_qb->add_constraint('end', '>=', time());
            $product_qb->end_group();
        }

        $this->_request_data['products'] = $product_qb->execute();
    }

    /**
     * This function does the output.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_list($handler_id, array &$data)
    {
        if ($this->controller) {
            $data['view_group'] = $this->controller->get_content_html();
        } else {
            $data['view_group'] = $this->datamanager->get_content_html();
        }

        midcom_show_style('group_header');

        if (count($data['products']) > 0) {
            midcom_show_style('group_products_grid');
            midcom_show_style('group_products_footer');
        } else {
            midcom_show_style('group_empty');
        }
        midcom_show_style('group_footer');
    }

    /**
     * Update the context so that we get a complete breadcrumb line towards the current location.
     */
    private function _update_breadcrumb_line()
    {
        $tmp = $this->_master->update_breadcrumb_line($this->_request_data['group']);
        midcom_core_context::get()->set_custom_key('midcom.helper.nav.breadcrumb', $tmp);
    }
}
