<?php
namespace CosmicBeacon\CriticalSection;

interface CriticalSection
{
    /**
     * @param string   $code
     * @param int|null $timeoutSeconds
     *
     * @throws \CosmicBeacon\CriticalSection\Exception\UnableToObtainLockException
     *
     * @return bool
     */
    public function enter($code, $timeoutSeconds = null);

    /**
     * @param string $code
     */
    public function leave($code);

    /**
     * @param string $code
     *
     * @return bool
     */
    public function canEnter($code);
}
