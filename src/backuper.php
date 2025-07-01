<?php
include __DIR__ . '/../vendor/autoload.php';
use Dallgoot\Yaml\Yaml;

class Backuper {
    protected $yaml;
    protected $dir;
    protected $configFile;
    protected $startTimeMs;

    public function __construct(?string $dir = null, string $configFile = '.backup.yml') {
        $this->dir = realpath($dir ?? __DIR__) . DIRECTORY_SEPARATOR;
        $this->configFile = $configFile;
        $this->startTimeMs = microtime(true);
        $yamlFile = $this->getPath($this->configFile);
        if(!file_exists($yamlFile)) {
            $this->log("Configuration file not found: " . $yamlFile, 'error');
            return false;
        }
        $this->yaml = Yaml::parseFile($yamlFile);
        $this->run();
    }

    protected function getPath(string $path): string {
        return rtrim(($path[0] === '/' || $path[0] === '\\') ? $path : $this->dir . $path, '/|\\');
    }

    protected function getBackupHandler() {
        $backup = new ServerBackup;
        $backup->setErrorHandler(function($message, array $data = []) {
            $this->log($message, 'warning');
        });
        $backup->setLogHandler(function($message, array $data = []) {
            $this->log($message);
        });
        return $backup;
    }

    protected function log(string $message, ?string $type = null) {
        echo date('[Y-m-d H:i:s] ');
        echo '[' . sprintf('%09f', (microtime(true) - $this->startTimeMs)) . 's] ';
        
        if(!is_null($type)) {
            echo "[" . strtoupper($type) . "] ";    
        }
        echo $message . PHP_EOL;
    }

    protected function parseEnv($item, ?array $env = null): object|array {
        if(is_array($env)){
            $envVars = $env;
        } else {
            $envFiles = ['.env'];
            $envVars = getenv();

            if(isset($item->env_file)){
                if(is_string($item->env_file)) {
                    $envFiles[] = $item->env_file;
                } elseif(is_array($item->env_file)) {
                    $envFiles = array_merge($envFiles, $item->env_file);
                }
            }

            $envFiles = array_unique($envFiles);
            foreach($envFiles as $envFile){
                $envFile = $this->getPath($envFile);
                if(!file_exists($envFile)) {
                    $this->log("Environment file not found: {$envFile}", 'warning');
                    continue;
                }
                $envVars = array_merge($envVars, parse_ini_file($envFile, true));
            }
        }

        foreach($item as $key => $value) {
            if(is_array($item)){
                $reference = &$item[$key];
            } elseif(is_object($item)){
                $reference = &$item->$key;
            }

            if(is_string($value)) {
                $reference = preg_replace_callback('/\$\{([a-zA-Z0-9_]+)\}/', function($matches) use ($envVars) {
                    if(!isset($envVars[$matches[1]])) {
                        $this->log("Environment variable '{$matches[1]}' not found", 'warning');
                        return $matches[0];
                    }

                    return $envVars[$matches[1]];
                }, $value);
            } elseif(is_object($value) || is_array($value)) {
                $reference = $this->parseEnv($value, $envVars);
            }
        }
        return $item;
    }

    public function run(){
        $keys = get_object_vars($this->yaml);

        foreach($keys as $projectName => $item){
            $backup = $this->getBackupHandler();
            $item = $this->parseEnv($item);

            // Add storages
            if(isset($item->storages) && is_array($item->storages)) {
                foreach($item->storages as $storage) {
                    if(is_string($storage)){
                        $backup->addPath($this->getPath($storage));
                    } elseif(is_object($storage)) {
                        if(isset($storage->path) && isset($storage->dest)) {
                            $backup->addPath($this->getPath($storage->path), $storage->dest);
                        } elseif(isset($storage->path)) {
                            $backup->addPath($this->getPath($storage->path));
                        }
                    }
                }
            }

            // Add databases
            if(isset($item->databases) && is_array($item->databases)) {
                foreach($item->databases as $db) {
                    if(is_object($db)) {
                        if(isset($db->host) && isset($db->name) && isset($db->user) && isset($db->pass)) {
                            $tables = (isset($db->tables) && is_array($db->tables)) ? $db->tables : [];
                            $type = isset($db->type) ? $db->type : 'mysql';
                            $charset = isset($db->charset) ? $db->charset : 'utf8';
                            $port = isset($db->port) ? $db->port : 3306;
                            $backup->addDatabase($db->host, $db->name, $db->user, $db->pass, $tables, $type, $charset, $port);
                        }
                    }
                }
            }

            $filename = $projectName . '-%Y-%m-%d-%H-%i-%s.zip';
            $storage = 'local';
            $auth = [];
            $path = $this->dir;

            if(isset($item->backup) && is_object($item->backup)){
                $filename = isset($item->backup->filename) ? $item->backup->filename : $filename;
                $storage = isset($item->backup->storage) ? $item->backup->storage : $storage;
                $auth = isset($item->backup->auth) ? $item->backup->auth : $auth;
                $path = isset($item->backup->path) ? $this->getPath($item->backup->path) : $path;
            }

            $path = rtrim($path, '/|\\') . '/';
            $filename = $this->processFilename($filename);

            switch($storage) {
                case 'yandex':
                    $tmpFile = sys_get_temp_dir() . '/' . $filename;
                    $backup->createBackup($tmpFile);
                    $token = $auth['token'] ?? '';
                    $backup->uploadYandexDisk($token, $path, true);
                    break;
                
                case 'local':
                default:
                    $backup->createBackup($path . '/' . $filename);
            }
        }
    }

    protected function processFilename(string $filename): string {
        return preg_replace_callback(
            '/%([a-zA-Z])/',
            function($matches) {
                return date($matches[1]);
            },
            $filename
        );
    }
}

set_time_limit(0);
(new Backuper(getcwd()));