<?php

namespace Flurry\TestCase;

abstract class XPathTestCase extends \PHPUnit_Framework_TestCase {

	protected abstract function getDomDocument();

	protected function assertXPathMatch($expected, $xpath, 
		$message = null, \DOMNode $context = null)
	{
		$dom = $this->getDomDocument();
		$xpathObj = new \DOMXPath($dom);

		$context = $context == null
			? $dom->documentElement
			: $context;

		$res = $xpathObj->evaluate($xpath, $context);

		$this->assertEquals(
			$expected,
			$res,
			$message);
	}
}
