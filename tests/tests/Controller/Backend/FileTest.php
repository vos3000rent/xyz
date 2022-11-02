<?php

namespace Concrete\Tests\Controller\Backend;

use Concrete\Controller\Backend\File;
use Concrete\Core\Error\UserMessageException;
use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

class FileTest extends \PHPUnit_Framework_TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @dataProvider remoteUrlsToTry
     * @see File::checkRemoteURlsToImport()
     */
    public function testCheckRemoteURlsToImport()
    {
        $exception = func_get_args();
        $urls = array_shift($exception);

        $controller = new File();

        // Note odd capitalization of "URL" in this method name
        $accessor = new \ReflectionMethod($controller, 'checkRemoteURlsToImport');
        $accessor->setAccessible(true);
        $closure = $accessor->getClosure($controller);

        // Expect an exception if one was provided, otherwise we expect to complete successfully
        if ($exception) {
            call_user_func_array([$this, 'setExpectedExceptionRegExp'], $exception);
        }

        $closure((array)$urls);

        if (!$exception) {
            // Run a successful condition to track the test
            $this->assertTrue(true);
        }
    }

    public function remoteUrlsToTry()
    {
        // Local IP
        $simpleIp = '127.0.0.1';
        $hex = '0x7f000001';
        $octal = '0177.0000.0000.0001';
        $octal2 = '0177.0.0.1';
        $octal3 = '017700000001';
        $integer = '2130706433';

        // Test hexadecimal IPs get caught as local
        yield ['https://' . $simpleIp, UserMessageException::class, '/The URL ".+?" is not valid./'];
        yield ['https://' . $hex, UserMessageException::class, '/The URL ".+?" is not valid./'];
        yield ['https://' . $octal, UserMessageException::class, '/The URL ".+?" is not valid./'];
        yield ['https://' . $octal2, UserMessageException::class, '/The URL ".+?" is not valid./'];
        yield ['https://' . $octal3, UserMessageException::class, '/The URL ".+?" is not valid./'];
        yield ['https://' . $integer, UserMessageException::class, '/The URL ".+?" is not valid./'];

        // Remote IP
        $simpleIp = '8.8.8.8';
        $hex = '0x08080808';
        $octal = '0010.0010.0010.0010';
        $octal2 = '0010.8.8.0010';
        $octal3 = '01002004010';
        $integer = '134744072';

        // Test hexadecimal IPs get caught as local
        yield ['http://' . $simpleIp]; // This is allowed because it's an external IP
        yield ['http://' . $hex, UserMessageException::class, '/The URL ".+?" is not valid./'];
        yield ['http://' . $octal]; // This form is allowed because it at least converts properly in ip-lib
        yield ['http://' . $octal2]; // Same as the first octal
        yield ['http://' . $octal3, UserMessageException::class, '/The URL ".+?" is not valid./'];
        yield ['http://' . $integer, UserMessageException::class, '/The URL ".+?" is not valid./'];
    }

}
