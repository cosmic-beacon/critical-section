<?php
namespace CB\Tests\CriticalSection;

use CosmicBeacon\CriticalSection\PostgreSQLCriticalSection;

/**
 * Test is done with 2 database connections to check if second connection could not get a lock because first connection
 * has it.
 */
class PostgreSQLCriticalSectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PostgreSQLCriticalSection
     */
    private $criticalSection1;

    /**
     * @var PostgreSQLCriticalSection
     */
    private $criticalSection2;

    /**
     * @var \PDO
     */
    private $pdo1;

    /**
     * @var \PDO
     */
    private $pdo2;

    public function setUp()
    {
        try {
            $this->pdo1 = new \PDO('pgsql:host=localhost;port=5432;dbname=cb_critical_section_test;user=postgres');
            $this->pdo2 = new \PDO('pgsql:host=localhost;port=5432;dbname=cb_critical_section_test;user=postgres');
        } catch (\PDOException $e) {
            $this->markTestSkipped($e->getMessage());
        }
        $this->criticalSection1 = new PostgreSQLCriticalSection($this->pdo1);
        $this->criticalSection2 = new PostgreSQLCriticalSection($this->pdo2);
    }

    public function testEnter()
    {
        $this->assertTrue($this->criticalSection2->canEnter('example_enter'));
        $this->criticalSection1->enter('example_enter');
        $this->assertFalse($this->criticalSection2->canEnter('example_enter'));
        $this->criticalSection1->leave('example_enter');
        $this->assertTrue($this->criticalSection2->canEnter('example_enter'));
    }

    public function testEnterWithTimeout()
    {
        $this->criticalSection1->enter('cs_timeout');
        $this->assertFalse($this->criticalSection2->canEnter('cs_timeout'));

        $timestamp = time();
        $this->assertFalse($this->criticalSection2->enter('cs_timeout', 2));
        $this->assertTrue($timestamp + 2 <= time());

        $this->criticalSection1->leave('cs_timeout');
        $this->assertTrue($this->criticalSection2->enter('cs_timeout', 2));
    }

    public function testMultipleEnters()
    {
        $this->assertTrue($this->criticalSection2->canEnter('example1'));
        $this->assertTrue($this->criticalSection2->canEnter('example2'));

        $this->criticalSection1->enter('example1');
        $this->criticalSection1->enter('example2');

        $this->assertFalse($this->criticalSection2->canEnter('example1'));
        $this->assertFalse($this->criticalSection2->canEnter('example2'));

        $this->criticalSection1->leave('example1');
        $this->criticalSection1->leave('example2');

        $this->assertTrue($this->criticalSection2->canEnter('example1'));
        $this->assertTrue($this->criticalSection2->canEnter('example2'));
    }

    public function testMoreLeavesWontDoAnything()
    {
        $this->assertTrue($this->criticalSection2->canEnter('example_leave'));
        $this->criticalSection1->leave('example_leave');
        $this->assertTrue($this->criticalSection2->canEnter('example_leave'));
        $this->criticalSection1->leave('example_leave');
        $this->assertTrue($this->criticalSection2->canEnter('example_leave'));
    }
}
