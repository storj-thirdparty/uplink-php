<?php

namespace Storj\Uplink\Test\Edge;

use DateInterval;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Storj\Uplink\Edge\Config;
use Storj\Uplink\Exception\Edge\DialFailed;
use Storj\Uplink\Permission;
use Storj\Uplink\Test\Util;

class RegisterAccessTest extends TestCase
{
    public function testRegisterAccessHappyFlow(): void
    {
        $authService = getenv('AUTH_SERVICE_ADDRESS');
        if (!$authService) {
            $this->markTestSkipped('No auth service address set');
        }

        $edgeConfig = (new Config())->withAuthServiceAddress($authService);
        $edge = Util::uplink()->edgeServices();

        // set expiry so we don't pollute the Auth service prod datebase when running tests against prod
        $tomorrow = (new DateTimeImmutable())->add(new DateInterval('P1D'));
        $access = Util::access()->share(
            Permission::readOnlyPermission()
                ->notAfter($tomorrow)
        );

        $credentials = $edge->registerAccess($edgeConfig, $access);

        // just to check it isn't empty or garbage
        self::assertMatchesRegularExpression('~\w{10,200}~', $credentials->getAccessKeyId());
        self::assertMatchesRegularExpression('~\w{10,200}~', $credentials->getSecretKey());
        self::assertMatchesRegularExpression('~https://[\w.]{10,200}~', $credentials->getEndpoint());
    }

    public function testRegisterAccessInvalidAddress(): void
    {
        // No DRPC auth service is running at this address.
        $edgeConfig = (new Config())->withAuthServiceAddress('storj.io:33463');
        $uplink = Util::uplink();
        $edge = $uplink->edgeServices();

        $this->expectException(DialFailed::class);
        $edge->registerAccess($edgeConfig, Util::access());
    }
}
