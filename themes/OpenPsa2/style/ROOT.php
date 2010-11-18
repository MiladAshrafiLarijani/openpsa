<?php
if (!defined('MIDCOM_STATIC_URL'))
{
    define('MIDCOM_STATIC_URL', '/midcom-static');
}

$pref_found = false;
$width = midgard_admin_asgard_plugin::get_preference('openpsa2_offset');
if ($width !== false)
{
    $navigation_width = $width;
    $content_offset = $width;
    $pref_found = true;
}

$topic = midcom::get_context_data(MIDCOM_CONTEXT_CONTENTTOPIC);

echo "<?xml version=\"1.0\"?>\n";
?>
<!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo midcom::i18n()->get_current_language(); ?>">
    <head>
        <meta http-equiv="Content-Type" content="text/xhtml; charset=utf-8" />
        <title><?php echo $topic->extra . ': ' . midcom::get_context_data(MIDCOM_CONTEXT_PAGETITLE); ?> - <(title)> OpenPSA</title>
        <link type="image/x-icon" href="<?php echo MIDCOM_STATIC_URL; ?>/org.openpsa.core/openpsa-16x16.png" rel="shortcut icon"/>
        <?php
        midcom::add_link_head(array('rel' => 'stylesheet',  'type' => 'text/css', 'href' => MIDCOM_STATIC_URL . '/OpenPsa2/style.css', 'media' => 'screen,projection'));
        midcom::add_link_head(array('rel' => 'stylesheet',  'type' => 'text/css', 'href' => MIDCOM_STATIC_URL . '/OpenPsa2/content.css', 'media' => 'all'));
        midcom::add_link_head(array('rel' => 'stylesheet',  'type' => 'text/css', 'href' => MIDCOM_STATIC_URL . '/OpenPsa2/print.css', 'media' => 'print'));

        midcom::enable_jquery();
        midcom::add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.core.min.js');
        midcom::add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.widget.min.js');
        midcom::add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.mouse.min.js');
        midcom::add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.draggable.min.js');
        midcom::add_jsfile(MIDCOM_STATIC_URL . '/OpenPsa2/ui.js');
        midcom::add_jscript("var MIDGARD_ROOT = '" . midcom_connection::get_url('self') . "';");

        midcom::print_head_elements();

        if ($pref_found)
        {?>
              <style type="text/css">
                #container #leftframe
                {
                 width: &(navigation_width);px;
                }

                #container #content
                {
                  margin-left: &(content_offset);px;
                }
            </style>
        <?php } ?>
    </head>
    <body<?php midcom::print_jsonload(); ?>>
        <(toolbar)>
        <div id="container">
          <div id="leftframe">
            <div id="branding">
                <(logo)>
            </div>
            <div id="nav">
                <(navigation)>
            </div>
          </div>
          <div id="content">
              <div id="content-menu">
                  <(breadcrumb)>
                  <div class="context">
                      <(userinfo)>
                      <(search)>
                  </div>
              </div>
              <div id="org_openpsa_toolbar" class="org_openpsa_toolbar">
                  <(toolbar-bottom)>
              </div>
              <div id="org_openpsa_messagearea">
              </div>
              <div id="content-text">
                  <(content)>
              </div>
          </div>
       </div>
<?php
//Display any UI messages added to stack on PHP level
midcom::uimessages()->show();
?>
    <script type="text/javascript">
        jQuery(document).ready(org_openpsa_jsqueue.execute());
    </script>
    </body>
</html>