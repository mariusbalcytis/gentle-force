<?php

namespace Maba\GentleForce\InMemory;

class MicrotimeProvider
{
    /**
     * @var float
     */
    private $mockedMicrotime;

    /**
     * @param float $mockedMicrotime
     */
    public function setMockedMicrotime($mockedMicrotime)
    {
        $this->mockedMicrotime = $mockedMicrotime;
    }

    public function getMicrotime()
    {
        return $this->mockedMicrotime !== null ? $this->mockedMicrotime : microtime(true);
    }
}
