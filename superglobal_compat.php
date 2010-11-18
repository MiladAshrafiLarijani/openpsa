<?php
midcom::service('componentloader')->load('org.openpsa.core');

midcom::componentloader->load('org.openpsa.core');

midcom::componentloader()->load('org.openpsa.core');
midcom::enable_jquery();

echo midcom::service('auth')->user->lastname;

echo midcom::auth()->user->lastname;
?>

<?php
class midcom
{
    private static $_application;

    /**
     * JS/CSS merger service. Still WIP and disabled by default
     */
    public static $jscss = false;

    /**
     * Set this variable to true during the handle phase of your component to
     * not show the site's style around the component output. This is mainly
     * targeted at XML output like RSS feeds and similar things. The output
     * handler of the site, excluding the style-init/-finish tags will be executed
     * immediately after the handle phase, and midcom->finish() is called
     * automatically afterwards, thus ending the request.
     *
     * Changing this flag after the handle phase or for dynamically loaded
     * components won't change anything.
     *
     * @var boolean
     * @access public
     */
    public static $skip_page_style = false;

    public static function setup($auth, $cache)
    {
        self::$_application = new midcom_application();
        //Register services
        self::$_application->auth = $auth;
        self::$_application->cache = $cache;
        self::$_application->initialize();
    }

    public static function serviceloader()
    {
    	return self::$_application->serviceloader;
    }

    public static function i18n()
    {
        return self::$_application->i18n;
    }

    public static function componentloader()
    {
        return self::$_application->componentloader;
    }

    public static function dbclassloader()
    {
        return self::$_application->dbclassloader;
    }

    public static function dbfactory()
    {
        return self::$_application->dbfactory;
    }

    public static function style()
    {
        return self::$_application->style;
    }

    public static function permalinks()
    {
        return self::$_application->permalinks;
    }

    public static function tmp()
    {
        return self::$_application->tmp;
    }

    public static function toolbars()
    {
        return self::$_application->toolbars;
    }

    public static function uimessages()
    {
        return self::$_application->uimessages;
    }

    public static function metadata()
    {
        return self::$_application->metadata;
    }

    public static function rcs()
    {
        return self::$_application->rcs;
    }

    public static function session()
    {
        return self::$_application->session;
    }

    public static function indexer()
    {
        return self::$_application->indexer;
    }


    /**
     * Main MidCOM initialization.
     *
     * Note, that there is no constructor so that initialize can already populate global references.
     *
     * Initialize the Application class. Sets all private variables to a predefined
     * state. $node should be set to the midcom root-node GUID.
     * $prefix can be a prefix, which is appended to midcom_connection::get_url('self') (i.e. the
     * Midgard Page URL). This may be needed when MidCOM is run by wrapper.
     */
    public static function initialize()
    {
        self::$_application->initialize();
    }

    /* *************************************************************************
     * Main Application control framework:
     * start_services - Starts all available services
     * code-init      - Handle the current request
     * content        - Show the current pages output
     * dynamic_load   - Dynamically load and execute a URL
     * finish         - Cleanup Work
     */

    /**
     * Initialize the URL parser and process the request.
     *
     * This function must be called before any output starts.
     *
     * @see _process()
     */
    public static function codeinit()
    {
        self::$_application->codeinit();
    }

    /**
     * Display the output of the component
     *
     * This function must be called in the content area of the
     * Style template, usually <(content)>.
     */
    public static function content()
    {
        self::$_application->content();
    }

    /**
     * Dynamically execute a subrequest and insert its output in place of the
     * function call.
     *
     * <b>Important Note</b> As with the Midgard Parser, dynamic_load strips a
     * trailing .html from the argument list before actually parsing it.
     *
     * Under MIDCOM_REQUEST_CONTENT it tries to load the component referenced with
     * the URL $url and executes it as if it was used as primary component.
     * Additional configuration parameters can be appended through the parameter
     * $config.
     *
     * This is only possible if the system is in the Page-Style output phase. It
     * cannot be used within code-init or during the output phase of another
     * component.
     *
     * Setting MIDCOM_REQUEST_CONTENTADM loads the content administration interface
     * of the component. The semantics is the same as for any other MidCOM run with
     * the following exceptions:
     *
     * - This function can (and usually will be) called during the content output phase
     *   of the system.
     * - A call to generate_error will result in a regular error page output if
     *   we still are in the code-init phase.
     *
     * Example code, executed on a sites Homepage, it will load the news listing from
     * the given URL and display it using a substyle of the node style that is assigned
     * to the loaded one:
     *
     * <code>
     * $blog = '/blog/latest/3/';
     * $substyle = 'homepage';
     * midcom::dynamic_load("/midcom-substyle-{$substyle}/{$blog}");
     * </code>
     *
     * <B>Danger, Will Robinson:</b>
     *
     * Be aware, that the call to another component will most certainly overwrite global
     * variables that you are currently using. A common mistake is this:
     *
     * <code>
     * global $view;
     * midcom::dynamic_load($view['url1']);
     * // You will most probably fail, could even loop infinitely!
     * midcom::dynamic_load($view['url2']);
     * </code>
     *
     * The reason why this usually fails is, that the $view you have been using during
     * the first call was overwritten by the other component during it, $view['url2']
     * is now empty. If you are now on the homepage, the homepage would start loading
     * itself again and again.
     *
     * Therefore, be sure to save the variables locally (remember, the style invocation
     * is in function context):
     *
     * <code>
     * $view = $GLOBALS['view'];
     * midcom::dynamic_load($view['url1']);
     * midcom::dynamic_load($view['url2']);
     * </code>
     *
     * Results of dynamic_loads are cached, by default with the system cache strategy
     * but you can specify separate cache strategy for the DL in the config array like so
     * <code>
     * midcom::dynamic_load("/midcom-substyle-{$substyle}/{$newsticker}", array('cache_module_content_caching_strategy' => 'public'))
     * </code>
     *
     * You can use only less specific strategy than the global strategy, ie basically you're limited to 'memberships' and 'public' as
     * values if the global strategy is 'user' and to 'public' the global strategy is 'memberships', failure to adhere to this
     * rule will result to weird cache behavior.
     *
     * @param string $url                The URL, relative to the Midgard Page, that is to be requested.
     * @param Array $config              A key=>value array with any configuration overrides.
     * @param int $type                  Request type (by default MIDCOM_REQUEST_CONTENT)
     * @return int                       The ID of the newly created context.
     */
    public static function dynamic_load($url, $config = array(), $type = MIDCOM_REQUEST_CONTENT, $pass_get = false)
    {
        return self::$_application->dynamic_load($url, $config, $type, $pass_get);
    }

    /**
     * Exit from the framework, execute after all output has been made.
     *
     * Does all necessary clean-up work. Must be called after output is completed as
     * the last call of any MidCOM Page. Best Practice: call it at the end of the ROOT
     * style element.
     *
     * <b>WARNING:</b> Anything done after calling this method will be lost.
     */
    public static function finish()
    {
        self::$_application->finish();
    }

    /* *************************************************************************
     * Framework Access Helper functions
     */

    public static function generate_host_url($host)
    {
        return self::$_application->generate_host_url($host);
    }

    /**
     * Retrieves the name of the current host, fully qualified with protocol and
     * port.
     *
     * @return string Full Hostname (http[s]://www.my.domain.com[:1234])
     */
    public static function get_host_name()
    {
        return self::$_application->generate_host_url();
    }

    /**
     * Return the prefix required to build relative links on the current site.
     * This includes the http[s] prefix, the hosts port (if necessary) and the
     * base url of the Midgard Page. Be aware, that this does *not* point to the
     * base host of the site.
     *
     * e.g. something like http[s]://www.domain.com[:8080]/host_prefix/page_prefix/
     *
     * @return string The current MidCOM page URL prefix.
     */
    public static function get_page_prefix()
    {
        return self::$_application->get_page_prefix();
    }

    /**
     * Return the prefix required to build relative links on the current site.
     * This includes the http[s] prefix, the hosts port (if necessary) and the
     * base url of the main host. This is not necessarily the currently active
     * MidCOM Page however, use the get_page_prefix() function for that.
     *
     * e.g. something like http[s]://www.domain.com[:8080]/host_prefix/
     *
     * @return string The host's root page URL prefix.
     */
    public static function get_host_prefix()
    {
        return self::$_application->get_host_prefix();
    }

    /**
     * Return the reference to the component loader.
     *
     * @return midcom_helper__componentloader The reference of the component loader in use.
     */
    public static function get_component_loader()
    {
        return self::$_application->get_component_loader();
    }

    /**
     * If the system is in the output phase (see above), the systemwide low-level
     * NAP interface can be accessed through this function. A reference is returned.
     *
     * This function maintains one NAP Class per context. Usually this is enough,
     * since you mostly will access it in context 0, the default. The problem is, that
     * this is not 100% efficient: If you instantiate two different NAP Classes in
     * different contexts both referring to the same root node, you will get two
     * different instances.
     *
     * If the system has not completed the can_handle phase, this method fails and
     * returns false.
     *
     * <b>Note:</b> Direct use of this function is discouraged, use the class
     * midcom_helper_nav instead.
     *
     * @param int $contextid    The ID of the context for which a NAP class is requested.
     * @return midcom_helper__basicnav&    A reference to the basicnav instance in the application cache.
     * @see midcom_helper_nav
     */
    public static function & get_basic_nav($contextid)
    {
        return self::$_application->get_basic_nav($contextid);
    }

    /**
     * Access the MidCOM component context
     *
     * Returns Component Context Information associated to the component with the
     * context ID $contextid identified by $key. Omitting $contextid will yield
     * the variable from the current context.
     *
     * If the context ID is invalid, false is returned and $midcom_errstr will be set
     * accordingly. Be sure to compare the data type with it, since a "0" will evaluate
     * to false if compared with "==" instead of "===".
     *
     * @param int param1    Either the ID of the context (two parameters) or the key requested (one parameter).
     * @param int param2    Either the key requested (two parameters) or null (one parameter, the default).
     * @return mixed    The content of the key being requested.
     */
    public static function get_context_data($param1, $param2 = null)
    {
        return self::$_application->get_context_data($param1, $param2);
    }

    /**
     * Update the component context
     *
     * This function sets a variable of the current or the given component context.
     *
     * @param mixed $value    The value to be stored
     * @param int $param1    See get_context_data()
     * @param int $param2    See get_context_data()
     * @see get_context_data()
     * @access private
     */
    public static function _set_context_data($value, $param1, $param2 = null)
    {
        self::$_application->_set_context_data($value, $param1, $param2);
    }

    /**
     * Store arbitrary, component-specific information in the component context
     *
     * This method allows you to get custom data to a given context.
     * The system will automatically associate this data with the component from the
     * currently active context. You cannot access the custom data of any other
     * component this way, it is private to the context. You may attach information
     * to other contexts, which will be associated with the current component, so
     * you have a clean namespace independently from which component or context you
     * are operating of. All calls honor references of passed data, so you can use
     * this for central controlling objects.
     *
     * Note, that if you are working from a library like the datamanager is, you
     * cannot override the component association done by the system. Instead you
     * should add your libraries name (like midcom.helper.datamanager) as a prefix,
     * separated by a dot. I know, that this is not really an elegant solution and
     * that it actually breaks with the encapsulation I want, but I don't have a
     * better solution yet.
     *
     * Be aware, that this function works by-reference instead of by-value.
     *
     * A complete example could look like this:
     *
     * <code>
     * class my_component_class_one {
     *     function init () {
     *         midcom::set_custom_context_data('classone', $this);
     *     }
     * }
     *
     * class my_component_class_two {
     *        var one;
     *     function my_component_class_two () {
     *         $this->one =& midcom::get_custom_context_data('classone');
     *     }
     * }
     * </code>
     *
     * A very important caveat of PHP references can be seen here: You must never give
     * a reference to $this outside of a class within a constructor. class_one uses an
     * init function therefore. See the PHP documentation for a few more details on
     * all this. Component authors can usually safely set this up at the beginning of the
     * can_handle() and/or handle() calls.
     *
     * Also, be careful with the references you use here, things like this can easily
     * get quite confusing.
     *
     * @param mixed $key        The key associated to the value.
     * @param mixed $value    The value to store. (This is stored by-reference!)
     * @param int $contextid    The context to associated this data with (defaults to the current context)
     * @see get_custom_context_data()
     */
    public static function set_custom_context_data ($key, &$value, $contextid = null)
    {
        self::$_application->set_custom_context_data($key, $value, $contextid);
    }

    /**
     * Retrieve arbitrary, component-specific information in the component context
     *
     * The set call defaults to the current context, the get call's semantics are as
     * with get_context_data.
     *
     * Note, that if you are working from a library like the datamanager is, you
     * cannot override the component association done by the system. Instead you
     * should add your libraries name (like midcom.helper.datamanager) as a prefix,
     * separated by a dot. I know, that this is not really an elegant solution and
     * that it actually breaks with the encapsulation I want, but I don't have a
     * better solution yet.
     *
     * A complete example can be found with set_custom_context_data.
     *
     * @param int $param1    See get_context_data()
     * @param int $param2    See get_context_data()
     * @return mixed        The requested value, which is returned by Reference!
     * @see get_context_data()
     * @see set_custom_context_data()
     */
    public static function & get_custom_context_data($param1, $param2 = null)
    {
        return self::$_application->get_custom_context_data($param1, $param2);
    }

    /**
     * Returns the ID of the currently active context. This is FALSE if there is no
     * context running.
     *
     * @return int The context ID.
     */
    public static function get_current_context ()
    {
        return self::$_application->get_current_context();
    }

    /**
     * Returns the complete context data array
     *
     * @return array The data of all contexts
     */
    function get_all_contexts ()
    {
        return self::$_application->get_all_contexts();
    }

    /**
     * Appends a substyle after the currently selected component style.
     *
     * Appends a substyle after the currently selected component style, effectively
     * enabling a depth of more then one style during substyle selection. This is only
     * effective if done during the handle phase of the component and allows the
     * component. The currently selected substyle therefore is now searched one level
     * deeper below "subStyle".
     *
     * The system must have completed the CAN_HANDLE Phase before this function will
     * be available.
     *
     * @param string $newsub The substyle to append.
     * @see substyle_prepend()
     */
    public static function substyle_append ($newsub)
    {
        self::$_application->substyle_append($newsub);
    }

    /**
     * Prepends a substyle before the currently selected component style.
     *
     * Prepends a substyle before the currently selected component style, effectively
     * enabling a depth of more then one style during substyle selection. This is only
     * effective if done during the handle phase of the component and allows the
     * component. The currently selected substyle therefore is now searched one level
     * deeper below "subStyle".
     *
     * The system must have completed the CAN_HANDLE Phase before this function will
     * be available.
     *
     * @param string $newsub The substyle to prepend.
     * @see substyle_append()
     */
    public static function substyle_prepend($newsub)
    {
        self::$_application->substyle_prepend($newsub);
    }

    /**
     * Load a code library
     *
     * This will load the pure-code library denoted by the MidCOM Path $path. It will
     * return true if the component truly was a pure-code library, false otherwise.
     * If the component loader cannot load the component, generate_error will be
     * called by it.
     *
     * Common example:
     *
     * <code>
     * midcom::load_library('midcom.helper.datamanager');
     * </code>
     *
     * @param string $path    The name of the code library to load.
     * @return boolean            Indicates whether the library was successfully loaded.
     */
    public static function load_library($path)
    {
        return self::$_application->load_library($path);
    }

    /**
     * Returns the Client Status Array which gives you all available information about
     * the client accessing us.
     *
     * Currently incorporated is a recognition of client OS and client browser.
     *
     * <b>NOTE:</b> Be careful if you rely on this information, the system does not check
     * for invervening Proxies yet.
     *
     * <b>WARNING:</b> If the caching engine is running, you must not rely on this
     * information! You should set no_cache in these cases.
     *
     * @return Array    Key/Value Array with the client information (see MIDCOM_CLIENT_... constants)
     */
    public static function get_client()
    {
        return self::$_application->get_client();
    }

    /**
     * Sends a header out to the client.
     *
     * This function is syntactically identical to
     * the regular PHP header() function, but is integrated into the framework. Every
     * Header you sent must go through this function or it might be lost later on;
     * this is especially important with caching.
     *
     * @param string $header    The header to send.
     * @param integer $response_code HTTP response code to send with the header
     */
    public static function header($header, $response_code = null)
    {
        self::$_application->header($header, $response_code);
    }

    /**
     * Sets a new context, doing some minor sanity checking.
     *
     * @return boolean    Indicating if the switch was successful.
     * @access private
     */
    public static function _set_current_context($id)
    {
        return self::$_application->_set_current_context($id);
    }

    /**
     * Get the current MidCOM processing state.
     *
     * @return int    One of the MIDCOM_STATUS_... constants indicating current state.
     */
    public static function get_status()
    {
        return self::$_application->get_status();
    }

    /**
     * Return a reference to a given service.
     *
     * Returns the MidCOM Object Service indicated by $name. If the service cannot be
     * found, an HTTP 500 is triggered.
     *
     * See the documentation of the various services for further details.
     *
     * @param string $name        The name of the service being requested.
     * @return mixed    A reference(!) to the service requested.
     */
    public static function get_service($name)
    {
        return self::$_application->get_service($name);
    }

    /**
     * Sets the page title for the current context.
     *
     * This can be retrieved by accessing the component context key
     * MIDCOM_CONTEXT_PAGETITLE.
     *
     * @param string $string    The title to set.
     */
    public static function set_pagetitle($string)
    {
        self::$_application->set_pagetitle($string);
    }


    /* *************************************************************************
     * Generic Helper Functions not directly related with MidCOM:
     *
     * generate_error     - Generate HTTP Error
     * serve_snippet      - Serves snippet including all necessary headers
     * serve_attachment   - Serves attachment including all necessary headers
     * _l10n_edit_wrapper - Invokes the l10n string editor
     * add_jsfile         - Add a JavaScript URL to the load queue
     * add_jscript        - Add JavaScript code to the load queue
     * add_jsonload       - Add a JavaScript method call to the bodies onload tag
     * add_object_head    - Add object links to the page's head.
     * add_style_head     - Add style  tags to the page's head.
     * add_meta_head      - Add metatags to the page's head.
     * print_head_elements     - Print the queued-up JavaScript code (for inclusion in the HEAD section)
     * print jsonload     - Prints the onload command if required (for inclusion as a BODY attribute)
     * check_memberships  - Checks whether the user is in a given group
     * relocate           - executes a HTTP relocation to the given URL
     */

    /**
     * Generate an error page.
     *
     * This function is a small helper, that will display a simple HTML Page reporting
     * the error described by $httpcode and $message. The $httpcode is also used to
     * send an appropriate HTTP Response.
     *
     * For a list of the allowed HTTP codes see the MIDCOM_ERR... constants
     *
     * <b>Note:</b> This function will call _midcom_stop_request() after it is finished.
     *
     * @see midcom_exception_handler::show()
     * @param int $httpcode        The error code to send.
     * @param string $message    The message to print.
     */
    public static function generate_error($httpcode, $message)
    {
        self::$_application->generate_error($httpcode, $message);
    }

    /**
     * Deliver a snippet to the client.
     *
     * This function is a copy of serve_attachment, but instead of serving attachments
     * it can serve the code field of an arbitrary snippet. There is no checking on
     * permissions done here, the callee has to ensure this. See the URL methods
     * servesnippet(guid) for details.
     *
     * Two parameters can be used to influence the behavior of this method:
     * "midcom/content-type" will set the content-type header sent with the code
     * field's content. If this is not set, application/octet-stream is used as a
     * default. "midcom/expire" is a count of seconds used for content expiration,
     * both for the HTTP headers and for the caching engine. If this is no valid
     * integer or less then or equal to zero or not set, the value is set to "1".
     *
     * The last modified header is created by using the revised timestamp of the
     * snippet.
     *
     * Remember to also set the parameter "midcom/allow_serve" to "true" to clear the
     * snippet for serving.
     *
     * @param MidgardSnippet &$snippet    The snippet that should be delivered to the client.
     */
    public static function serve_snippet (& $snippet)
    {
        self::$_application->serve_snippet($snippet);
    }

    /**
     * Deliver a blob to the client.
     *
     * This is a replacement for mgd_serve_attachment that should work around most of
     * its bugs: It is missing all important HTTP Headers concerning file size,
     * modification date and expiration. It will not call _midcom_stop_request() when it is finished,
     * you still have to do that yourself. It will add the following HTTP Headers:
     *
     * - Cache-Control: public max-age=$expires
     * - Expires: GMT Date $now+$expires
     * - Last-Modified: GMT Date of the last modified timestamp of the Attachment
     * - Content-Length: The Length of the Attachment in Bytes
     * - Accept-Ranges: none
     *
     * This should enable caching of browsers for Navigation images and so on. You can
     * influence the expiration of the served attachment with the parameter $expires.
     * It is the time in seconds till the client should refetch the file. The default
     * for this is 24 hours. If you set it to "0" caching will be prohibited by
     * changing the sent headers like this:
     *
     * - Pragma: no-cache
     * - Cache-Control: no-cache
     * - Expires: Current GMT Date
     *
     * If expires is set to -1, which is the default as of 2.0.0 (it was 86400 earlier),
     * no expires header gets sent.
     *
     * @param MidgardAttachment &$attachment    A reference to the attachment to be delivered.
     * @param int $expires HTTP-Expires timeout in seconds, set this to 0 for uncacheable pages, or to -1 for no Expire header.
     */
    public static function serve_attachment(& $attachment, $expires = -1)
    {
        self::$_application->serve_attachment($attachment, $expires);
    }

    /**
     * Register JavaScript File for referring in the page.
     *
     * This allows MidCOM components to register JavaScript code
     * during page processing. The site style code can then query this queued-up code
     * at anytime it likes. The queue-up SHOULD be done during the code-init phase,
     * while the print_head_elements output SHOULD be included in the HTML HEAD area and
     * the HTTP onload attribute returned by print_jsonload SHOULD be included in the
     * BODY-tag. Note, that these suggestions are not enforced, if you want a JScript
     * clean site, just omit the print calls and you should be fine in almost all
     * cases.
     *
     * The sequence of the add_jsfile and add_jscript commands is kept stable.
     *
     * @param string $url    The URL to the file to-be referenced.
     * @param boolean $prepend Whether to add the JS include to beginning of includes
     * @see add_jscript()
     * @see add_jsonload()
     * @see print_head_elements()
     * @see print_jsonload()
     */
    public static function add_jsfile($url, $prepend = false)
    {
        self::$_application->add_jsfile($url, $prepend);
    }

    /**
     * Register JavaScript Code for output directly in the page.
     *
     * This allows MidCOM components to register JavaScript code
     * during page processing. The site style code can then query this queued-up code
     * at anytime it likes. The queue-up SHOULD be done during the code-init phase,
     * while the print_head_elements output SHOULD be included in the HTML HEAD area and
     * the HTTP onload attribute returned by print_jsonload SHOULD be included in the
     * BODY-tag. Note, that these suggestions are not enforced, if you want a JScript
     * clean site, just omit the print calls and you should be fine in almost all
     * cases.
     *
     * The sequence of the add_jsfile and add_jscript commands is kept stable.
     *
     * @param string $script    The code to be included directly in the page.
     * @see add_jsfile()
     * @see add_jsonload()
     * @see print_head_elements()
     * @see print_jsonload()
     */
    public static function add_jscript($script, $defer = '', $prepend = false)
    {
        self::$_application->add_jscript($script, $defer, $prepend);
    }

    /**
     * Register JavaScript snippets to jQuery states.
     *
     * This allows MidCOM components to register JavaScript code
     * to the jQuery states.
     * Possible ready states: document.ready
     *
     * @param string $script    The code to be included in the state.
     * @param string $state    The state where to include the code to. Defaults to document.ready
     * @see print_jquery_statuses()
     */
    public static function add_jquery_state_script($script, $state = 'document.ready')
    {
        self::$_application->add_jquery_state_script($script, $state);
    }

    /**
     * Register some object tags to be added to the head element.
     *
     * This allows MidCom components to register object tags to be placed in the
     * head section of the page.
     *
     * @param  string $script    The input between the <object></object> tags.
     * @param  array  $attributes Array of attribute=> value pairs to be placed in the tag.
     * @see print_head_elements()
     *
     */

    public static function add_object_head ($script, $attributes = null)
    {
        self::$_application->add_object_head($script, $attributes);
    }
    /**
     *  Register a metatag  to be added to the head element.
     *  This allows MidCom components to register metatags  to be placed in the
     *  head section of the page.
     *
     *  @param  array  $attributes Array of attribute=> value pairs to be placed in the tag.
     *  @see print_head_elements()
     */
    public static function add_meta_head($attributes = null)
    {
        self::$_application->add_meta_head($attributes);
    }

    /**
     * Register a styleblock / style link  to be added to the head element.
     * This allows MidCom components to register extra css sheets they wants to include.
     * in the head section of the page.
     *
     * @param  string $script    The input between the <style></style> tags.
     * @param  array  $attributes Array of attribute=> value pairs to be placed in the tag.
     * @see print_head_elements()
     */
    public static function add_style_head($script, $attributes = null)
    {
        self::$_application->add_style_head($script, $attributes);
    }

    /**
     * Register a linkelement to be placed in the pagehead.
     * This allows MidCom components to register extra css-links in the pagehead.
     * Example to use this to include a css link:
     * <code>
     * $attributes = array ('rel' => 'stylesheet',
     *                      'type' => 'text/css',
     *                      'href' => '/style.css'
     *                      );
     * $midcom->add_link_head($attributes);
     * </code>
     *
     *  @param  array  $attributes Array of attribute=> value pairs to be placed in the tag.
     *  @see print_head_elements()
     */
    public static function add_link_head( $attributes = null )
    {
        return self::$_application->add_link_head($attributes);
    }

    /**
     * Register a JavaScript method for the body onload event
     *
     * This allows MidCOM components to register JavaScript code
     * during page processing. The site style code can then query this queued-up code
     * at anytime it likes. The queue-up SHOULD be done during the code-init phase,
     * while the print_head_elements output SHOULD be included in the HTML HEAD area and
     * the HTTP onload attribute returned by print_jsonload SHOULD be included in the
     * BODY-tag. Note, that these suggestions are not enforced, if you want a JScript
     * clean site, just omit the print calls and you should be fine in almost all
     * cases.
     *
     * @param string $method    The name of the method to be called on page startup, including parameters but excluding the ';'.
     * @see add_jsfile()
     * @see add_jscript()
     * @see print_head_elements()
     * @see print_jsonload()
     */
    public static function add_jsonload($method)
    {
        self::$_application->add_jsonload($method);
    }

    /**
     * Echo the registered javascript code.
     *
     * This allows MidCOM components to register JavaScript code
     * during page processing. The site style code can then query this queued-up code
     * at anytime it likes. The queue-up SHOULD be done during the code-init phase,
     * while the print_head_elements output SHOULD be included in the HTML HEAD area and
     * the HTTP onload attribute returned by print_jsonload SHOULD be included in the
     * BODY-tag. Note, that these suggestions are not enforced, if you want a JScript
     * clean site, just omit the print calls and you should be fine in almost all
     * cases.
     *
     * The sequence of the add_jsfile and add_jscript commands is kept stable.
     *
     * This is usually called during the BODY region of your style:
     *
     * <code>
     * <HTML>
     *     <BODY <?php midcom::print_jsonload();?>>
     *            <!-- your actual body -->
     *     </BODY>
     * </HTML>
     * </code>
     *
     * @see add_jsfile()
     * @see add_jscript()
     * @see add_jsonload()
     * @see print_head_elements()
     */
    public static function print_jsonload()
    {
        self::$_application->print_jsonload();
    }

    /**
     * Echo the _head elements added.
     * This function echos the elements added by the add_(style|meta|link|object)_head
     * methods.
     *
     * Place the method within the <head> section of your page.
     *
     * This allows MidCOM components to register HEAD elements
     * during page processing. The site style code can then query this queued-up code
     * at anytime it likes. The queue-up SHOULD be done during the code-init phase,
     * while the print_head_elements output SHOULD be included in the HTML HEAD area and
     * the HTTP onload attribute returned by print_jsonload SHOULD be included in the
     * BODY-tag. Note, that these suggestions are not enforced, if you want a JScript
     * clean site, just omit the print calls and you should be fine in almost all
     * cases.
     *
     * @see add_link_head
     * @see add_object_head
     * @see add_style_head
     * @see add_meta_head
     * @see add_jsfile()
     * @see add_jscript()
     */
    public static function print_head_elements()
    {
        self::$_application->print_head_elements();
    }

    /**
     * Init jQuery
     *
     * This method adds jQuery support to the page
     *
     */
    public static function enable_jquery($version = null)
    {
        self::$_application->enable_jquery($version);
    }

    /**
     * Echo the jquery statuses
     *
     * This function echos the scripts added by the add_jquery_state_script
     * method.
     *
     * This method is called from print_head_elements method.
     *
     * @see add_jquery_state_script
     * @see print_head_elements
     */
    public static function print_jquery_statuses()
    {
        self::$_application->print_jquery_statuses();
    }

    /**
     * Relocate to another URL.
     *
     * Helper function to facilitate HTTP relocation (Location: ...) headers. The helper
     * actually can distinguish between site-local, absolute redirects and external
     * redirects. If you add an absolute URL starting with a "/", it will
     * automatically add an http[s]://$servername:$server_port in front of that URL;
     * note that the server_port is optional and only added if non-standard ports are
     * used. If the url does not start with http[s], it is taken as a URL relative to
     * the current anchor prefix, which gets prepended automatically (no other characters
     * as the anchor prefix get inserted).
     *
     * Fully qualified urls (starting with http[s]) are used as-is.
     *
     * Note, that this function automatically makes the page uncacheable, calls
     * midcom_finish and exit, so it will never return. If the headers have already
     * been sent, this will leave you with a partially completed page, so beware.
     *
     * @param string $url    The URL to redirect to, will be preprocessed as outlined above.
     * @param string $response_code HTTP response code to send with the relocation, from 3xx series
     */
    public static function relocate($url, $response_code = 302)
    {
        self::$_application->relocate($url, $response_code);
    }

    /**
     * Binds the current page view to a particular object. This will automatically connect such things like
     * metadata and toolbars to the correct object.
     *
     * @param DBAObject &$object The DBA class instance to bind to.
     * @param string $page_class String describing page type, will be used for substyling
     */
    public static function bind_view_to_object(&$object, $page_class = 'default')
    {
        self::$_application->bind_view_to_object($object, $page_class);
    }

    /**
     * This is a temporary transition function used to set the currently known and required
     * Request Metadata: The last modified timestamp and the permalink GUID.
     *
     * <i>Author's note:</i> This function is a temporary solution which is used until the
     * Request handling code of MidCOM has been rewritten. Hence the _26_ section in its name.
     * I have decided to put them into their own function instead of letting you access the
     * corresponding context keys directly. Thus, there is also corresponding getter-function,
     * which return you the set information here. Just don't worry where it is stored and use
     * the interface functions.
     *
     * You may set either of the arguments to NULL to enforce default usage (based on NAP).
     *
     * @param int $lastmodified The date of last modification of this request.
     * @param string $permalinkguid The GUID used to create a permalink for this request.
     * @see get_26_request_metadata()
     */
    public static function set_26_request_metadata($lastmodified, $permalinkguid)
    {
        self::$_application->set_26_request_metadata($lastmodified, $permalinkguid);
    }

    /**
     * This is a temporary transition function used to get the currently known and required
     * Request Metadata: The last modified timestamp and the permalink GUID.
     *
     * <i>Author's note:</i> This function is a temporary solution which is used until the
     * Request handling code of MidCOM has been rewritten. Hence the _26_ section in its name.
     * I have decided to put them into their own function instead of letting you access the
     * corresponding context keys directly. Thus, there is also corresponding setter-function,
     * which set the information returned here. Just don't worry where it is stored and use
     * the interface functions.
     *
     * @param int $context The context from which the request metadata should be retrieved. Omit
     *     to use the current context.
     * @return Array An array with the two keys 'lastmodified' and 'permalinkguid' containing the
     *     values set with the setter pendant. For ease of use, there is also a key 'permalink'
     *     which contains a ready-made permalink.
     * @see set_26_request_metadata()
     */
    public static function get_26_request_metadata($context = null)
    {
        return self::$_application->get_26_request_metadata($context);
    }

}

?>
