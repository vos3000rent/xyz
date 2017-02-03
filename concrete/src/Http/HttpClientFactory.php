<?php
namespace Concrete\Core\Http;

use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Support\Facade\Application;
use Zend\Http\Client;
use Zend\Http\Client\Adapter\Curl;
use Zend\Http\Client\Adapter\Socket;
use Zend\Http\Client\Adapter\Proxy;

class HttpClientFactory
{
    /**
     * Read the HTTP Client configuration.
     *
     * @param Repository $config
     *
     * @return array {
     *     all the options set in app.http_client
     *
     *     @var mixed $proxyhost
     *     @var mixed $proxyport
     *     @var mixed $proxyuser
     *     @var mixed $proxypass
     * }
     */
    protected function getOptions(Repository $config)
    {
        $result = $config->get('app.http_client');
        if (!is_array($result)) {
            $result = [];
        }
        $result['proxyhost'] = $config->get('concrete.proxy.host');
        $result['proxyport'] = $config->get('concrete.proxy.port');
        $result['proxyuser'] = $config->get('concrete.proxy.user');
        $result['proxypass'] = $config->get('concrete.proxy.password');

        return $result;
    }

    /**
     * Normalize the Zend HTTP Client options.
     *
     * @param array $options
     *
     * @return array
     */
    protected function normalizeOptions(array $options)
    {
        // Default values
        $result = [
            'adapter' => null,
            'sslverifypeer' => true,
            'sslverifypeername' => true,
            'proxyhost' => '',
            'proxyport' => 8080,
            'proxyuser' => '',
            'proxypass' => '',
            'sslcafile' => null,
            'sslcapath' => null,
            'connecttimeout' => 5,
            'timeout' => 60,
            'keepalive' => false,
            'maxredirects' => 5,
            'rfc3986strict' => false,
            'sslcert' => null,
            'sslpassphrase' => null,
            'storeresponse' => true,
            'streamtmpdir' => null,
            'strictredirects' => false,
            'useragent' => 'concrete5 CMS',
            'encodecookies' => true,
            'httpversion' => '1.1',
            'ssltransport' => 'tls',
            'sslallowselfsigned' => false,
            'persistent' => false,
        ];
        // Normalize the given options
        foreach ($options as $key => $value) {
            // Normalize the option key (exactly like Zend Http Client)
            $key = str_replace(['-', '_', ' ', '.'], '', strtolower($key));
            switch ($key) {
                // Not nullable boolean values
                case 'sslverifypeer':
                case 'sslverifypeername':
                case 'keepalive':
                case 'rfc3986strict':
                case 'storeresponse':
                case 'strictredirects':
                case 'encodecookies':
                case 'sslallowselfsigned':
                case 'persistent':
                    if ($value !== null && $value !== '') {
                        $result[$key] = (bool) $value;
                    }
                    break;
                // Not nullable integer values
                case 'proxyport':
                case 'connecttimeout':
                case 'timeout':
                case 'maxredirects':
                    if (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))) {
                        $result[$key] = (int) $value;
                    }
                    break;
                // Strings thay may be empty
                case 'proxyhost':
                case 'proxyuser':
                case 'proxypass':
                case 'sslpassphrase':
                case 'useragent':
                    $result[$key] = is_string($value) ? $value : '';
                    break;
                // Strings thay can't be empty
                case 'sslcafile':
                case 'sslcapath':
                case 'sslcert':
                case 'httpversion':
                case 'ssltransport':
                case 'streamtmpdir':
                    if (is_string($value) && $value !== '') {
                        $result[$key] = $value;
                    }
                    break;
                // Special cases
                case 'adapter':
                    if ((is_string($value) && $value !== '') || is_object($value)) {
                        $result[$key] = $value;
                    }
                    break;
                // Pass through
                default:
                    $result[$key] = $value;
                    break;
            }
        }
        // Set the temporary directory
        if ($result['streamtmpdir'] === null) {
            $result['streamtmpdir'] = Application::getFacadeApplication()->make('helper/file')->getTemporaryDirectory();
        }
        // Fix proxy options (Zend uses some of these values even if they are null)
        if ($result['proxyhost'] === '') {
            unset($result['proxyhost']);
            unset($result['proxyport']);
            unset($result['proxyuser']);
            unset($result['proxypass']);
        } elseif ($result['proxyuser'] === '') {
            unset($result['proxyuser']);
            unset($result['proxypass']);
        }
        // Don't set sslpassphrase is sslcert is not set (Zend tries to set the sslpassphrase even if no sslcert has been specified)
        if ($result['sslcert'] === null) {
            unset($result['sslpassphrase']);
        }
        // Don't pass sslcafile/sslcapath if sslverifypeer is false
        if ($result['sslverifypeer'] === false) {
            unset($result['sslcafile']);
            unset($result['sslcapath']);
        }

        return $result;
    }

    /**
     * Create a new HTTP Client instance starting from configuration.
     *
     * @param Repository $config
     * @param string|object|null $adapter
     *
     * @return Client
     */
    public function createFromConfig(Repository $config, $adapter = null)
    {
        $options = $this->getOptions($config);

        return $this->createFromOptions($options, $adapter);
    }

    /**
     * Create a new HTTP Client instance starting from configuration.
     *
     * @param array $options See the app.http_client values at concrete/config/app.php, plus the concrete.proxy values at concrete/config/concrete.php.
     * @param mixed $adapter The adapter to use (defaults to Curl adapter if curl extension is installed, otherwise we'll use the Socket adapter)
     *
     * @return Client
     */
    public function createFromOptions(array $options, $adapter = null)
    {
        $options = $this->normalizeOptions($options);
        if ((is_string($adapter) && $adapter !== '') || is_object($adapter)) {
            $options['adapter'] = $adapter;
        } elseif ($options['adapter'] === null) {
            if (function_exists('curl_init')) {
                $options['adapter'] = Curl::class;
            } elseif (isset($options['proxyhost'])) {
                $options['adapter'] = Proxy::class;
            } else {
                $options['adapter'] = Socket::class;
            }
        }

        $client = new Client(null, $options);

        return $client;
    }
}
