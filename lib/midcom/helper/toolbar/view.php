<?php
/**
 * @package midcom.helper
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This class is a view toolbar class.
 *
 * @package midcom.helper
 */
class midcom_helper_toolbar_view extends midcom_helper_toolbar
{
    /**
     * @param string $class_style The class style tag for the UL.
     * @param string $id_style The id style tag for the UL.
     */
    public function __construct($class_style = null, $id_style = null)
    {
        $config = midcom::get()->config;
        $class_style = $class_style ?: $config->get('toolbars_view_style_class');
        $id_style = $id_style ?: $config->get('toolbars_view_style_id');
        parent::__construct($class_style, $id_style);
    }

    public function bind_object(midcom_core_dbaobject $object)
    {
        $this->add_approval_controls($object);

        if ($object->can_do('midgard:update'))
        {
            $this->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "__ais/folder/metadata/{$object->guid}/",
                    MIDCOM_TOOLBAR_LABEL => midcom::get()->i18n->get_string('edit metadata', 'midcom.admin.folder'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/metadata.png',
                    MIDCOM_TOOLBAR_ACCESSKEY => 'm',
                )
            );
            $this->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "__ais/folder/move/{$object->guid}/",
                    MIDCOM_TOOLBAR_LABEL => midcom::get()->i18n->get_string('move', 'midcom.admin.folder'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/save-as.png',
                    MIDCOM_TOOLBAR_ENABLED => is_a($object, 'midcom_db_article')
                )
            );
            $this->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => midcom_connection::get_url('self') . "__mfa/asgard/object/open/{$object->guid}/",
                    MIDCOM_TOOLBAR_LABEL => midcom::get()->i18n->get_string('manage object', 'midgard.admin.asgard'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/properties.png',
                    MIDCOM_TOOLBAR_ENABLED => midcom::get()->auth->can_user_do('midgard.admin.asgard:access', null, 'midgard_admin_asgard_plugin', 'midgard.admin.asgard') && midcom::get()->auth->can_user_do('midgard.admin.asgard:manage_objects', null, 'midgard_admin_asgard_plugin'),
                )
            );
        }

        if (   midcom::get()->config->get('midcom_services_rcs_enable')
            && $object->can_do('midgard:update')
            && $object->_use_rcs)
        {
            $this->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "__ais/rcs/{$object->guid}/",
                    MIDCOM_TOOLBAR_LABEL => midcom::get()->i18n->get_string('show history', 'no.bergfald.rcs'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/history.png',
                    MIDCOM_TOOLBAR_ACCESSKEY => 'v',
                )
            );
        }
    }

    public function add_approval_controls(midcom_core_dbaobject $object, $add_accesskey = false)
    {
        if (midcom::get()->config->get('metadata_approval'))
        {
            if ($object->metadata->is_approved())
            {
                $icon = 'stock-icons/16x16/page-approved.png';
                if (   !midcom::get()->config->get('show_hidden_objects')
                    && !$object->metadata->is_visible())
                {
                    // Take scheduling into account
                    $icon = 'stock-icons/16x16/page-approved-notpublished.png';
                }
                $this->add_item
                (
                    array
                    (
                        MIDCOM_TOOLBAR_URL => "__ais/folder/unapprove/",
                        MIDCOM_TOOLBAR_LABEL => midcom::get()->i18n->get_string('unapprove', 'midcom'),
                        MIDCOM_TOOLBAR_HELPTEXT => midcom::get()->i18n->get_string('approved', 'midcom'),
                        MIDCOM_TOOLBAR_ICON => $icon,
                        MIDCOM_TOOLBAR_POST => true,
                        MIDCOM_TOOLBAR_POST_HIDDENARGS => array
                        (
                            'guid' => $object->guid,
                            'return_to' => $_SERVER['REQUEST_URI'],
                        ),
                        MIDCOM_TOOLBAR_ACCESSKEY => ($add_accesskey) ? 'u' : null,
                        MIDCOM_TOOLBAR_ENABLED => $object->can_do('midcom:approve'),
                    )
                );
            }
            else
            {
                $icon = 'stock-icons/16x16/page-notapproved.png';
                if (   !midcom::get()->config->get('show_hidden_objects')
                    && !$object->metadata->is_visible())
                {
                    // Take scheduling into account
                    $icon = 'stock-icons/16x16/page-notapproved-notpublished.png';
                }
                $this->add_item
                (
                    array
                    (
                        MIDCOM_TOOLBAR_URL => "__ais/folder/approve/",
                        MIDCOM_TOOLBAR_LABEL => midcom::get()->i18n->get_string('approve', 'midcom'),
                        MIDCOM_TOOLBAR_HELPTEXT => midcom::get()->i18n->get_string('unapproved', 'midcom'),
                        MIDCOM_TOOLBAR_ICON => $icon,
                        MIDCOM_TOOLBAR_POST => true,
                        MIDCOM_TOOLBAR_POST_HIDDENARGS => array
                        (
                            'guid' => $object->guid,
                            'return_to' => $_SERVER['REQUEST_URI'],
                        ),
                        MIDCOM_TOOLBAR_ACCESSKEY => ($add_accesskey) ? 'a' : null,
                        MIDCOM_TOOLBAR_ENABLED => $object->can_do('midcom:approve'),
                    )
                );
            }
        }
    }
}