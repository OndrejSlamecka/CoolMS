<?php


/**
 * Test class for Paths.
 * Generated by PHPUnit on 2012-02-20 at 14:01:42.
 */
class PathsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers {className}::{origMethodName}
     * @todo Implement testSanitize().
     */
    public function testSanitize()
    {
        $path = '\\';
        $res = Application\Utils\Paths::sanitize($path);
        $this->assertEquals($res, '/');

        $path = '/////';
        $res = Application\Utils\Paths::sanitize($path);
        $this->assertEquals($res, '/');

        $path = '/../';
        $res = Application\Utils\Paths::sanitize($path);
        $this->assertEquals($res, '/');
    }

}

?>
