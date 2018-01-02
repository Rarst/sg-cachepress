<?php

use Brain\Monkey;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

class SG_CachePress_TestCase extends PHPUnit\Framework\TestCase {

	use MockeryPHPUnitIntegration;

	protected function setUp() {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown() {
		Monkey\tearDown();
		parent::tearDown();
	}
}
