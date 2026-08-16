<?php

declare(strict_types=1);

namespace RaccoonWP;

use Dotenv\Dotenv;

/**
 * Bootstraps a RaccoonWP install: validates requirements, loads the environment
 * from .env, and defines the WordPress constants that wp-config would otherwise
 * set by hand.
 *
 * @package RaccoonWP
 */
class RaccoonApp
{
    /** Minimum supported PHP version. */
    const MIN_PHP_VERSION = '7.4';
    /** Name of the WP installation directory. */
    const WP_INSTALL_DIRECTORY_NAME = 'wp';
    /** Name of the content (wp-content) directory. */
    const CONTENT_DIRECTORY_NAME = 'core';
    /** Name of the public (web root) directory. */
    const WEB_ROOT_DIRECTORY_NAME = 'public';

    protected static ?RaccoonApp $instance = null;

    protected string $root_dir;
    protected string $public_root_dir;
    protected string $wp_dir_name;
    protected string $content_dir_name;

    /**
     * @param string|null $root_directory         Root directory of the project.
     * @param string|null $web_root_directory_name Web root directory, e.g. 'public' or 'web'.
     */
    public function __construct(?string $root_directory = null, ?string $web_root_directory_name = null)
    {
        try {
            $this->checkRequirements();
        } catch (\Exception $e) {
            echo 'Error during requirements check: ', $e->getMessage(), "\n";
            die();
        }

        $this->root_dir = !empty($root_directory) ? $root_directory : '';
        $this->public_root_dir = $this->root_dir . '/' . ($web_root_directory_name ?? self::WEB_ROOT_DIRECTORY_NAME);
        $this->wp_dir_name = self::WP_INSTALL_DIRECTORY_NAME;
        $this->content_dir_name = self::CONTENT_DIRECTORY_NAME;
    }

    /**
     * Checks that the system meets the minimum requirements (PHP version, Dotenv present).
     *
     * @throws \Exception When the PHP version is too low or Dotenv is missing.
     */
    protected function checkRequirements(): void
    {
        if (version_compare(phpversion(), self::MIN_PHP_VERSION, '<')) {
            throw new \Exception('Your installed PHP version is not sufficient to run RaccoonWP project');
        }
        if (!class_exists('\\Dotenv\\Dotenv')) {
            throw new \Exception('Dotenv extension is missing. Did you run composer install?');
        }
    }

    /**
     * Loads the environment and defines the WordPress constants.
     */
    public function initialize(): void
    {
        $this->initializeDotEnv();
        $this->setupApplication();

        self::setInstance($this);
    }

    /**
     * Loads environment configuration from the .env file via Dotenv.
     */
    protected function initializeDotEnv(): void
    {
        $dotenv = Dotenv::createUnsafeImmutable($this->root_dir);

        if (file_exists($this->root_dir . '/.env')) {
            $dotenv->load();
            $dotenv->required(
                [
                    'DB_NAME',
                    'DB_USER',
                    'DB_PASSWORD',
                    'WP_HOME'
                ]
            );
        }
    }

    /**
     * Defines the WordPress constants, the same way the original wp-config does.
     */
    protected function setupApplication(): void
    {
        /**
         * Fix SSL behind reverse proxy.
         * See https://codex.wordpress.org/Function_Reference/is_ssl#Notes
         */
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            $_SERVER['HTTPS'] = 'on';
        }

        $env_type = !empty($_ENV['WP_ENV']) ? $_ENV['WP_ENV'] : 'production';
        define('WP_ENV', $env_type);

        //compatibility with the new 5.5 wp_get_environment_type()
        if (!defined('WP_ENVIRONMENT_TYPE')) {
            define('WP_ENVIRONMENT_TYPE', $env_type);
        }

        $this->maybeLoadEnvironmentConfiguration($env_type);
        $this->maybeLoadCommonEnvironmentsConfiguration();

        /**
         * DB settings
         */
        define('DB_NAME', $_ENV['DB_NAME']);
        define('DB_USER', $_ENV['DB_USER']);
        define('DB_PASSWORD', $_ENV['DB_PASSWORD']);
        define('DB_HOST', !empty($_ENV['DB_HOST']) ? $_ENV['DB_HOST'] : 'localhost');
        define('DB_CHARSET', !empty($_ENV['DB_CHARSET']) ? $_ENV['DB_CHARSET'] : 'utf8mb4');
        define('DB_COLLATE', !empty($_ENV['DB_COLLATE']) ? $_ENV['DB_COLLATE'] : '');

        define('AUTH_KEY', $_ENV['AUTH_KEY'] ?? '');
        define('SECURE_AUTH_KEY', $_ENV['SECURE_AUTH_KEY'] ?? '');
        define('LOGGED_IN_KEY', $_ENV['LOGGED_IN_KEY'] ?? '');
        define('NONCE_KEY', $_ENV['NONCE_KEY'] ?? '');
        define('AUTH_SALT', $_ENV['AUTH_SALT'] ?? '');
        define('SECURE_AUTH_SALT', $_ENV['SECURE_AUTH_SALT'] ?? '');
        define('LOGGED_IN_SALT', $_ENV['LOGGED_IN_SALT'] ?? '');
        define('NONCE_SALT', $_ENV['NONCE_SALT'] ?? '');

        //URLs and directories
        if (!defined('WP_HOME')) {
            define('WP_HOME', $_ENV['WP_HOME']);
        }

        if (!empty($_ENV['WP_SITEURL'])) {
            define('WP_SITEURL', $_ENV['WP_SITEURL']);
        } else {
            define('WP_SITEURL', $_ENV['WP_HOME'] . '/' . $this->wp_dir_name);
        }

        if (!defined('WP_CONTENT_DIR')) {
            define('WP_CONTENT_DIR', $this->public_root_dir . '/' . $this->content_dir_name);
        }

        if (!defined('WP_CONTENT_URL')) {
            define('WP_CONTENT_URL', WP_HOME . '/' . $this->content_dir_name);
        }

        //Disallow WordPress from updating itself automatically since we manage its version in Composer
        if (!defined('AUTOMATIC_UPDATER_DISABLED')) {
            define('AUTOMATIC_UPDATER_DISABLED', true);
        }

        // Set the absolute path to the WordPress directory.
        if (!defined('ABSPATH')) {
            define('ABSPATH', $this->public_root_dir . '/' . $this->wp_dir_name . '/');
        }
    }

    /**
     * Loads environment-specific configuration if the file exists.
     * Place your configuration file into /configuration/{ENV_NAME}.php,
     * for example /configuration/production.php.
     *
     * Configuration files hold environment data that can and should live in the
     * repository, contrary to .env files with DB credentials and other secrets.
     */
    protected function maybeLoadEnvironmentConfiguration(string $env_type): void
    {
        if (strlen(trim($env_type)) === 0) {
            return;
        }

        $conf_file = $this->root_dir . '/configuration/' . $env_type . '.php';
        if (file_exists($conf_file)) {
            require_once $conf_file;
        }
    }

    /**
     * Loads the environments' common configuration if the file exists.
     * Place your configuration file into /configuration/common.php.
     */
    protected function maybeLoadCommonEnvironmentsConfiguration(): void
    {
        $conf_file = $this->root_dir . '/configuration/common.php';
        if (file_exists($conf_file)) {
            require_once $conf_file;
        }
    }

    /**
     * Stores the current instance of the class.
     */
    public static function setInstance(RaccoonApp $instance): self
    {
        return self::$instance = $instance;
    }

    /**
     * Returns the initialized instance.
     *
     * @throws \RuntimeException When called before initialize().
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            throw new \RuntimeException('RaccoonApp has not been initialized yet.');
        }

        return self::$instance;
    }
}
