<?php
namespace Tests\Unit;
use PHPUnit\Framework\TestCase;
class HelperTest extends TestCase
{
	public function testNullOrEmptyStringWithNull()
	{
		$this->assertTrue(isNullOrEmptyString(null));
	}

	public function testNullOrEmptyStringWithEmptyString()
	{
		$this->assertTrue(isNullOrEmptyString(''));
	}

	public function testNullOrEmptyStringWithFullString()
	{
		$this->assertFalse(isNullOrEmptyString('text'));
	}

	public function testGetBaseDirectory()
	{
		$this->assertFalse(isNullOrEmptyString(getBaseDirectory()));
	}
}