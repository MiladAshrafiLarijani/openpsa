<?php
/**
 * @package openpsa.setup
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

namespace openpsa\setup;

/**
 * Simple installer class. Sets up a mgd2 configuration and DB
 *
 * @package openpsa.setup
 */
class mgd2installer extends installer
{
    protected $_io;

    protected $_vendor_dir;

    protected $_config_name;

    public function __construct($io, $vendor_dir)
    {
        $this->_io = $io;
        $this->_vendor_dir = $vendor_dir;
    }

    protected function _get_config_name($prefer_default = true)
    {
        if (empty($this->_config_name))
        {
            $default = $this->_load_default('config_name');
            if (empty($default))
            {
                $midgard = \midgard_connection::get_instance();
                if ($midgard->is_connected())
                {
                    // The actual config filename is not available at this point, so
                    // we take the DB name and hope for the best...
                    $default = $midgard->config->database;
                }
                else
                {
                    $path = ini_get('midgard.configuration_file');
                    $default = preg_replace('/\/.+\//', '', $path);
                }
            }
            if (   $prefer_default === true
                && !empty($default))
            {
                $this->_config_name = $default;
            }
            else
            {
                $this->_config_name = $this->_io->ask('<question>Please enter config name:</question> ', $default);
                $this->_save_default('config_name', $this->_config_name);
            }
        }
        return $this->_config_name;
    }

    protected function _load_default($key = null)
    {
        $defaults = array();
        $defaults_file = $this->_vendor_dir . '/.openpsa_installer_defaults.php';
        if (file_exists($defaults_file))
        {
            $defaults = json_decode(file_get_contents($defaults_file), true);
        }
        if (null !== $key)
        {
            if (array_key_exists($key, $defaults))
            {
                return $defaults[$key];
            }
            return null;
        }
        return $defaults;
    }

    protected function _save_default($key, $value)
    {
        $defaults = $this->_load_default();
        $defaults_file = $this->_vendor_dir . '/.openpsa_installer_defaults.php';
        if (file_exists($defaults_file))
        {
            unlink($defaults_file);
        }
        $defaults[$key] = $value;
        file_put_contents($defaults_file, json_encode($defaults));
    }

    protected function _load_config($config_name = null)
    {
        if (null === $config_name)
        {
            $config_name = $this->_get_config_name();
        }
        $config = new \midgard_config();
        if (!$config->read_file($config_name, false))
        {
            throw new \Exception('Could not read config file ' . $config_name);
        }
        return $config;
    }

    public function get_schema_dir()
    {
        $config = $this->_load_config();
        return $config->sharedir . '/schema/';
    }

    public function init_project()
    {
        $config_name = $this->_get_config_name(false);
        $config_file = "/etc/midgard2/conf.d/" . $config_name;
        if (   file_exists($config_file)
            && !$this->_io->askConfirmation('<question>' . $config_file . ' already exists, override?</question> '))
        {
            $config = $this->_load_config($config_name);
        }
        else
        {
            $config = $this->_create_config($config_name);
        }

        $this->_prepare_database($config);
    }

    private function _prepare_database(\midgard_config $config)
    {
        $midgard = \midgard_connection::get_instance();
        $midgard->open_config($config);
        if (!$midgard->is_connected())
        {
            throw new \Exception("Failed to open config {$config->database}:" . $midgard->get_error_string());
        }
        if (!$config->create_blobdir())
        {
            throw new \Exception("Failed to create file attachment storage directory to {$config->blobdir}:" . $midgard->get_error_string());
        }

        // Create storage
        if (!\midgard_storage::create_base_storage())
        {
            if ($midgard->get_error_string() != 'MGD_ERR_OK')
            {
                throw new \Exception("Failed to create base database structures" . $midgard->get_error_string());
            }
        }

        $re = new \ReflectionExtension('midgard2');
        $classes = $re->getClasses();
        foreach ($classes as $refclass)
        {
            if (!$refclass->isSubclassOf('midgard_object'))
            {
                continue;
            }
            $type = $refclass->getName();

            \midgard_storage::create_class_storage($type);
            \midgard_storage::update_class_storage($type);
        }
    }

    private function _create_config($config_name)
    {
        $project_basedir = realpath('./');
        $openpsa_basedir = realpath($project_basedir . '/vendor/openpsa/midcom/');

        self::_prepare_dir('midgard');
        self::_prepare_dir('midgard/var');
        self::_prepare_dir('midgard/cache');
        self::_prepare_dir('midgard/share');
        self::_prepare_dir('midgard/share/views');
        self::_prepare_dir('midgard/rcs');
        self::_prepare_dir('midgard/blobs');
        self::_prepare_dir('midgard/log');

        self::_link($openpsa_basedir . '/config/midgard_auth_types.xml', $project_basedir . '/midgard/share/midgard_auth_types.xml', $this->_io);
        self::_link($openpsa_basedir . '/config/MidgardObjects.xml', $project_basedir . '/midgard/share/MidgardObjects.xml', $this->_io);

        // Create a config file
        $config = new \midgard_config();
        $config->dbtype = 'MySQL';
        $config->database = $config_name;
        $config->blobdir = $project_basedir . '/midgard/blobs';
        $config->sharedir = $project_basedir . '/midgard/share';
        $config->vardir = $project_basedir . '/midgard/var';
        $config->cachedir = $project_basedir . '/midgard/cache';
        $config->logfilename = $project_basedir . 'midgard/log/midgard.log';
        $config->loglevel = 'debug';
        if (!$config->save_file($config_name, false))
        {
            throw new \Exception("Failed to save config file " . $config_name);
        }

        $this->_io->write("Configuration file " . $config_name . " created.");
        return $config;
    }
}
?>