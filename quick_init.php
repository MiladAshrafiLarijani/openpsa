<?php

class openpsa_installer
{
    protected $_project_name;

    public function __construct($args)
    {
        if (!extension_loaded('midgard2'))
        {
            die("Midgard2 is not installed in your PHP environment.\n");
        }

        if (!class_exists('midgard_topic'))
        {
            $iterator = new DirectoryIterator('./schemas');
            foreach ($iterator as $fileinfo)
            {
                if (   $fileinfo->isFile()
                    && strpos($fileinfo->getFilename(), 'xml'))
                {
                    //@todo: how to determine correct schema dir?
                    $this->_link_file('schemas', $fileinfo->getFilename(), '/usr/share/midgard2/schema');
                }
            }
            $this->output('Schemas linked. Re-run installer to continue');
            exit(0);
        }


        if (empty($argv[1]))
        {
            $this->_project_name = $this->prompt('Enter project name', 'openpsa');
        }
        else
        {
            $this->_project_name = $argv[1];
        }
    }

    public function fail($message, $error_code = 1)
    {
        $this->output($message);
        exit($error_code);
    }

    public function output($message)
    {
        echo $message . "\n";
    }

    public function prompt($message, $default = null, array $options = array())
    {
        if (!empty($options))
        {
            foreach ($options as $i => $option)
            {
                if ($option == $default)
                {
                    $options[$i] = strtoupper($option);
                }
                else
                {
                    $options[$i] = strtolower($option);
                }
            }
            $message .= ' [' . implode('|', $options) . ']';
        }
        else if (null !== $default)
        {
            $message .= ' [' . $default . ']';
        }
        $this->output($message);

        $handle = fopen('php://stdin', 'r');
        $input = trim(fgets($handle));
        if (   empty($input)
            && null !== $default)
        {
            return $default;
        }
        return $input;
    }

    protected function _check_dir($directory)
    {
        if (   !is_dir($directory)
            && !mkdir($directory))
        {
            $this->fail("Failed to create directory " . $directory);
        }
    }

    protected function _link_file($path, $target, $link_directory)
    {
        $this->_check_dir($link_directory);
        if (   !file_exists($link_directory . '/' . $target)
            && !link(dirname(__FILE__) . '/' . $path . '/' . $target, $link_directory . '/' . $target))
        {
            $this->fail("Failed to create link " . $link_directory . '/' . $target);
        }
    }

    public function run()
    {
        $config_file = "/etc/midgard2/conf.d/" . $this->_project_name;
        if (file_exists($config_file))
        {
            switch ($this->prompt($config_file . " already exists, override?", 'n', array('y', 'n')))
            {
                case 'y':
                    unlink($config_file);
                    $config = $this->_create_config();
                    break;

                default:
                    $config = new midgard_config();
                    if (!$config->read_file($this->_project_name, false))
                    {
                        $this->fail('Could not read config file ' . $config_file);
                    }
            }
        }
        else
        {
            $config = $this->_create_config();
        }

        // Open a DB connection with the config
        $midgard = midgard_connection::get_instance();
        if (!$midgard->open_config($config))
        {
            $this->fail("Failed to open Midgard database connection to {$this->_project_name}: " . $midgard->get_error_string());
        }

        require_once 'tools/bootstrap.php';
        openpsa_prepare_database($config);
        openpsa_prepare_topics();
    }

    private function _create_config()
    {
        $this->_check_dir('/var/lib/' . $this->_project_name);
        $this->_check_dir('/var/cache/' . $this->_project_name);
        $this->_link_file('config', 'midgard_auth_types.xml', '/var/lib/' . $this->_project_name . '/share');
        $this->_link_file('config', 'MidgardObjects.xml', '/var/lib/' . $this->_project_name . '/share');

        // Create a config file
        $config = new midgard_config();
        $config->dbtype = 'SQLite';
        $config->database = $this->_project_name;
        $config->dbdir = '/var/lib/' . $this->_project_name;
        $config->blobdir = '/var/lib/' . $this->_project_name . '/blobs';
        $config->sharedir = '/var/lib/' . $this->_project_name . '/share';
        $config->vardir = '/var/lib/' . $this->_project_name;
        $config->cachedir = '/var/cache/' . $this->_project_name;
        $config->logfilename = '/var/log/' . $this->_project_name . '/midgard.log';
        $config->loglevel = 'debug';
        if (!$config->save_file($this->_project_name, false))
        {
            $this->fail("Failed to save Midgard2 config file to /etc/midgard2/conf.d");
        }
        $this->output("Configuration file /etc/midgard2/conf.d/" . $this->_project_name . " created.");
        return $config;
    }
}

$installer = new openpsa_installer($argv);
$installer->run();
?>
