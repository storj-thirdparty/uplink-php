<?php

namespace Storj\Uplink\Edge;

use FFI;
use Storj\Uplink\Internal\Scope;
use Storj\Uplink\Internal\Util;

/**
 * Parameters when connecting to edge services
 */
class Config
{
    /**
     * DRPC server e.g. auth.storjshare.io:7777.
     * Currently mandatory to set this manually.
     */
    private string $authServiceAddress = "";

    /**
     * Root certificate(s) or chain(s) against which Uplink checks the auth service.
     * In PEM format.
     * Intended to test against a self-hosted auth service or to improve security.
    */
    private string $certificatePem = "";

    public function withAuthServiceAddress(string $authServiceAddress): self
    {
        $self = clone $this;
        $self->authServiceAddress = $authServiceAddress;
        return $self;
    }

    public function withCertificatePem(string $certificatePem): self
    {
        $self = clone $this;
        $self->certificatePem = $certificatePem;
        return $self;
    }

    public function getAuthServiceAddress(): string
    {
        return $this->authServiceAddress;
    }

    public function getCertificatePem(): string
    {
        return $this->certificatePem;
    }

    /**
     * @internal
     */
    public function toCStruct(FFI $ffi, Scope $scope): FFI\CData
    {
        $cAuthServiceAddress = Util::createCString($this->authServiceAddress, $scope);
        $cCertificatePem = Util::createCString($this->certificatePem, $scope);

        $cConfig = $ffi->new('EdgeConfig');
        $cConfig->auth_service_address = $cAuthServiceAddress;
        $cConfig->certificate_pem = $cCertificatePem;

        return $cConfig;
    }
}
