<?php
/**
 * Placeholder test class
 *
 * @package PNPC_Pocket_Service_Desk
 */

use PHPUnit\Framework\TestCase;

/**
 * Placeholder test to ensure PHPUnit runs successfully.
 */
class Test_Placeholder extends TestCase {

	/**
	 * Test that assertions work.
	 */
	public function test_assertions() {
		$this->assertTrue( true );
		$this->assertFalse( false );
		$this->assertEquals( 1, 1 );
	}

	/**
	 * Test that constants are defined.
	 */
	public function test_constants() {
		$this->assertTrue( defined( 'PNPC_PSD_PLUGIN_DIR' ) );
		$this->assertTrue( defined( 'PNPC_PSD_VERSION' ) );
	}

	/**
	 * Test basic PHP functionality.
	 */
	public function test_php_version() {
		$this->assertGreaterThanOrEqual( 7.4, PHP_VERSION );
	}
}
