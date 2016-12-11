<?php
use PHPUnit\Framework\TestCase;

include_once('./Mystrom/core/class/mystrom.class.php');

/**
 * Test class for mystrom core class
 */
class mystromTest extends TestCase {
    public function testDoAuthentification(){
        $mystrom = new mystrom();
    }
}