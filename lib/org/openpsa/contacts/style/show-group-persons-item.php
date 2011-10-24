<?php
$nap = new midcom_helper_nav();
$node = $nap->get_node($nap->get_current_node());

// Display the member
$contact = new org_openpsa_widgets_contact($data['person']);
$contact->link = "{$node[MIDCOM_NAV_FULLURL]}person/{$data['person']->guid}/";
$contact->show_groups = false;

if ($data['member']->can_do('midgard:update'))
{
    $contact->extra_html = "<li>
        <input id=\"editable_title_{$data['person']->guid}_ajaxDefault\" value=\"" . $data['l10n']->get('<title>') . "\" type=\"hidden\" />
        <input id=\"editable_title_{$data['person']->guid}_ajaxUrl\" value=\"{$node[MIDCOM_NAV_FULLURL]}group/{$data['group']->guid}/update_member_title/\" type=\"hidden\" />
        <input id=\"editable_title_{$data['person']->guid}\" name=\"member_title[{$data['member']->id}]\" class=\"ajax_editable\" style=\"width: 80%;\" onfocus=\"ooAjaxFocus(this)\" onblur=\"ooAjaxBlur(this)\" value=\"{$data['member_title']}\" />
        </li>\n";
}
else
{
    $contact->extra_html = "<li>{$data['member_title']}</li>\n";
}

$contact->show();
?>