<?php

class MockModel extends \Flurry\Model {
	protected $table = 'test_table';
	protected $columns = ['col1', 'col2', 'col3'];
}

class ModelTest extends PHPUnit_Framework_TestCase {

	public function testScrubFields() {
		$model = new MockModel();
		$scrubbed = $model->scrubFields(['col1' => 1, 'col2' => 2]);
		$this->assertEquals(['col1' => 1, 'col2' => 2], $scrubbed);
	}

	public function testScrubFieldsInvalid() {
		$model = new MockModel();
		$scrubbed = $model->scrubFields(['col1' => 1, 'col2' => 2, 'invalid_col' => 8]);
		$this->assertEquals(['col1' => 1, 'col2' => 2], $scrubbed);
	}

	public function testCreateUpdateBindSQL() {
		$model = new MockModel();
		$sql = $model->createUpdateBindSQL(['col1', 'col2']);
		$this->assertEquals('update test_table set col1 = :col1, col2 = :col2', $sql);
	}

	public function testCreateSelect() {
		$model = new MockModel();
		$sql = $model->createSelect(['col1', 'col2']);
		$this->assertEquals('select col1, col2 from test_table', $sql);
	}

	public function testCreateSelectEmpty() {
		$model = new MockModel();
		$sql = $model->createSelect();
		$this->assertEquals('select * from test_table', $sql);
	}
}
