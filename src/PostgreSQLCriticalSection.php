<?php
namespace CosmicBeacon\CriticalSection;

class PostgreSQLCriticalSection implements CriticalSection
{
    /**
     * @var \PDO
     */
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function enter($code, $timeoutSeconds = null)
    {
        if ($timeoutSeconds === 0) {
            return $this->lockWithoutWait($code);
        } elseif ($timeoutSeconds === null) {
            return $this->lockWithWait($code);
        }

        $endTime = time() + $timeoutSeconds;
        while (!($lock = $this->lockWithoutWait($code)) && time() < $endTime) {
            sleep(1);
        }

        return $lock;
    }

    public function leave($code)
    {
        $this->unlock($code);
    }

    /**
     * @param string $code
     *
     * @return bool
     */
    public function canEnter($code)
    {
        $lockId = $this->getLockId($code);

        return $this->pdo->query("SELECT pg_try_advisory_lock($lockId), pg_advisory_unlock($lockId);")->fetch(\PDO::FETCH_NUM)[0];
    }

    private function getLockId($code)
    {
        return crc32($code);
    }

    /**
     * @param $code
     *
     * @return bool
     */
    public function lockWithWait($code)
    {
        $lockId = $this->getLockId($code);

        return $this->pdo->query("SELECT pg_advisory_lock($lockId);")->fetch(\PDO::FETCH_NUM)[0];
    }

    /**
     * @param $code
     *
     * @return bool
     */
    public function lockWithoutWait($code)
    {
        $lockId = $this->getLockId($code);

        return $this->pdo->query("SELECT pg_try_advisory_lock($lockId);")->fetch(\PDO::FETCH_NUM)[0];
    }

    /**
     * @param $code
     *
     * @return bool
     */
    public function unlock($code)
    {
        $lockId = $this->getLockId($code);

        return $this->pdo->query("SELECT pg_advisory_unlock($lockId);")->fetch(\PDO::FETCH_NUM)[0];
    }
}
