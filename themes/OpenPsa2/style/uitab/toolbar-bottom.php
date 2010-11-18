<?php
$context_id = midcom::get_current_context();
$back_button_name = midcom::i18n()->get_string("back" , "midcom");
//remove the back-button
//TODO: any better way to identify the back button ?

foreach(midcom::toolbars()->_toolbars[$context_id][MIDCOM_TOOLBAR_VIEW]->items as $key => $item)
{
   if (   $item[1] == $back_button_name
       || (    array_key_exists('HTTP_REFERER', $_SERVER) 
            && strpos($_SERVER['HTTP_REFERER'], $item[0]) !== false))
   {
       unset(midcom::toolbars()->_toolbars[$context_id][MIDCOM_TOOLBAR_VIEW]->items[$key]);
   }
}
midcom::toolbars()->show_view_toolbar();
?>