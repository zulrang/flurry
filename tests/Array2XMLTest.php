<?php

class Array2XMLTest extends \Flurry\TestCase\XPathTestCase {

	public function getDomDocument() 
	{
		return $this->domDocument;
	}

	public function getDomRootElement() 
	{
		return $this->rootElement;
	}

	public function testCreateXML() {

		$this->domDocument = \Flurry\Array2XML::createXML("BaseNode", array(
			'item1' => 'val',
			'number' => 52,
			'arr' => array(
				'@attributes' => array(
					'att1' => 'foo',
					'att2' => 9
				),
				'sub1' => 'arg',
				'sub2' => 81
			)
		));

		$this->assertInstanceOf('DOMDocument', $this->domDocument);

		$this->assertXPathMatch(1, 'count(/BaseNode/item1)', 'Incorrect number of item1 elements');
		$this->assertXPathMatch(52, 'number(/BaseNode/number)', 'number is not 52');
		$this->assertXPathMatch('arg', 'string(/BaseNode/arr/sub1)', 'arr/sub1 is not arg');

	}
}