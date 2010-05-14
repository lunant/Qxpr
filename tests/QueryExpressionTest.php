<?php
require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__).'/../Qxpr/QueryExpression.php';
require_once dirname(__FILE__).'/../Qxpr/shortcuts.php';

class QueryExpressionTest extends PHPUnit_Framework_TestCase {
	static $tables = array(
		'member_info', 'student_info', 'product_list',
		'order_list', 'order_product'
	);

	private $model; 
	private $dbh;
	
	function setUp() {
		
		$this->dbh = new PDO("mysql:host=localhost;dbname=qxpr", 'root', '!#ghkdlxld');
		Q::$Database = $this->dbh;
		$this->member = new QueryExpression($this->dbh, 'member_info');
		$this->createTestTable(self::$tables, 'testSchemaMysql.sql');
	}

	function tearDown() {
		$this->dropTable(self::$tables);
	}

	function assertIndexEquals($expected, $array, $index = 0) {
		$this->assertEquals($expected, $array[$index]);
	}

	function assertIndexContains($niddle, array $haystack, $index = 0) {

		if(isset($haystack[$index]))
			$this->assertContains($niddle, $haystack[$index]);
		else
			$this->fail();
	}
	
	function testBasic() {
		$t = $this->member;
		$this->assertEquals(reset($t->get('address1')->byName('name1')->q()), 'address11');
		$this->assertEquals(array('a'=>'1', 'b'=>'2'), array('a'=>'1', 'b'=>'2'));
		
		$this->assertEquals(new QueryExpression(Q::$Database, 'member_info'), Q('member_info'));
		$this->assertEquals($this->member->tableName(), "member_info");
	}
	
	function testSelect(){
		$t = $this->member;

		$this->assertContains('name1', $t->byNo(1)->q());
		$this->assertContains('name1', $t->by('no', 1)->q());

		$expected = 'email2@email.com';

		$this->assertContains($expected, $t->get('name', 'email')->byNo(2)->q());
		$this->assertContains($expected, $t->get(array('id', 'email'))->byNo(2)->q());	
		$this->assertNotInArray($expected, $t->get('id','name')->byNo(2)->q());
		
		$this->assertContains('name1', $t->q("SELECT * FROM `".$t->tableName()."` LIMIT 1"));
		$this->assertContains('name1', $t->byNo(9989)->q("SELECT * FROM `".$t->tableName()."` LIMIT 1"));
		$this->assertContains('name1', at($t->byNo(9989)->ql("SELECT * FROM `".$t->tableName()."` LIMIT 1"), 0));
		
		$this->assertContains('name5', at($t->ql(), 4));
		$this->assertContains('name5', at($t->QL(), 4));
		
		$this->assertEquals($t->get('email')->byName('name1')->getOne(), 'email1@email.com');
		$this->assertEquals($t->get('email')->byName('name2')->O(), 'email2@email.com');
		$this->assertNotEquals($t->get('email')->byName('name1')->byZipcode('zipcode2')->getOne(), 'email1@email.com');
		
		$this->assertContains('name1', $t->limit(1)->q());
		$this->assertContains('name2', $t->limit(1,1)->q());
		$this->assertIndexContains('name4', $t->limit(1, 3)->ql(), 2);
		$this->assertEquals($t->limit()->q(), $t->limit(0)->q());
		
		$this->assertEquals($t->get('*')->q(), $t->q());
		$this->assertEquals($t->get()->q(), $t->q());
		$this->assertEquals($t->byName()->q(), $t->byName('')->q());
		
		$this->assertContains('name1', $t->orByName('name1')->orByName('name2')->q());
		$this->assertContains('name2', at($t->orByName('name1')->orByName('name2')->ql(),1));
		$this->assertContains('name1', $t->byName('name1')->orByName('name2')->q());
		$this->assertContains('name2', at($t->andByName('name1')->orByName('name2')->ql(),1));
		$this->assertEquals($t->byName('name1')->q(), $t->andByName('name1')->q());	
		
		$this->assertContains('name3', $t->get('name')->where("no = 3")->q());
		$this->assertContains('name3', $t->get('name')->byName('name1')->where("no = 3")->q());
		
		$this->assertEquals($t->get('name')->byName('name1')->q(), $t->nameAs('test')->get('name')->byName('name1')->q());
		$this->assertEquals($t->nameAs('test')->get('name')->q(), $t->get('name')->nameAs('test')->q());
	}
	
	public function testWhereInArray() {
		$result = '';
        //var_dump(Q('member_info')->byNo('IN', array(1,3,4))->createSelectQuery());
		foreach(Q('member_info')->byNo('IN', array(1,3,4)) as $record)
			$result.= $record['name'];
		
		$this->assertEquals('name1name3name4', $result);
	}
	
	public function testWhereInString() {
		$result = '';
		foreach(Q('member_info')->byNo('IN', '(1,3,4)') as $record)
			$result.= $record['name'];
		
		$this->assertEquals('name1name3name4', $result);
	}
	
	function testOrder() {
		$this->assertContains('name1', $this->member->get('name')->order('no')->q());
		$this->assertContains('name5', $this->member->get('name')->order('no', 'desc')->q());
		
		$this->assertEquals(
			"SELECT * FROM member_info ORDER BY member_info.no ASC, member_info.name ASC",
			$this->member->order('no')->order('name')->createSelectQuery()
		);
				
		$this->assertEquals(
			$this->member->order('no')->asc('name')->createSelectQuery(),
			$this->member->order('no')->order('name', 'ASC')->createSelectQuery()
		);		
		$this->assertEquals(
			$this->member->order('no')->desc('name')->createSelectQuery(),
			$this->member->order('no')->order('name', 'DESC')->createSelectQuery()
		);
	}
	
	function testReverse() {
		
		$this->assertEquals(
			"SELECT * FROM member_info ORDER BY member_info.no DESC, member_info.name ASC",
			$this->member->order('no')->order('name', 'DESC')->reverse()->createSelectQuery()
		);
	}
	
	function testSQLFunction(){
		$this->assertEquals(
			at($this->member->get('SUM(no)')->q(), 0), 15);
	}
	
	function testGroup() {
		$this->assertContains('name1', $this->member->get('name')->group('no')->q());
		$this->assertContains('name1', $this->member->get('name')->group('no')->order('name')->q());
	}

	function testApply(){
		$t = $this->member;
		$m = clone $this->member;
		$t->get('no', 'state')->apply();
		$this->assertEquals(Q('member_info')->get('no', 'state')->createSelectQuery(), $t->createSelectQuery());
		
		$m->get('no', 'state');
		$this->assertNotEquals(Q('member_info')->get('no', 'state')->q(), $m->q());
	}

	function testPK() {
		$this->assertEquals($this->member->PK(), 'no');
	}
	
	function testIterator() {
		$result = array();
		foreach(Q('member_info') as $record)
			array_push($result, $record);
		$this->assertEquals(Q('member_info')->ql(), $result);
		
		foreach($this->member->byName('name2') as $record);
		$this->assertContains('email2@email.com', $record);
	}
	
	function testCount() {
		$this->assertEquals(5, count(Q('member_info')));
		$this->assertEquals(1, count(Q('member_info')->byName('name1')));
	}
	
	function testOtherCondition() {
		$this->assertEquals(count($this->member->byNo('>', 3)), 2);
	}
	
	function testArrayAccess() {
		$this->assertEquals($this->member[0]['name'], 'name1');
		$this->assertEquals($this->member['email'], $this->member[0]['email']);
		
		$this->member->byName('name2');
		$this->member['email'] = "email2@email.com";
		$this->assertEquals($this->member->get('email')->getOne(), "email2@email.com");	
	}
	
	function testObjectAccess() {	
		$this->assertEquals($this->member->byName('name2')->email, "email2@email.com");
		Q('member_info')->byName('name2')->email = 'email2Modify@email.com';
		$this->assertEquals($this->member->get('email')->byName('name2')->getOne(), "email2Modify@email.com");
	}
	
	function testJoinSelect() {
		$t = $this->member;
		$p = Q('product_list');
		$ol = Q('order_list');
		$op = Q('order_product');
		$s = Q('student_info');
		
		$this->assertEquals($t->get('name')->byNo(1)->q(), $t->join($s, array('no','member'))->get('name')->byNo(1)->q());
		
		$this->assertEquals($t->get('name')->byNo(1)->q(), $t->leftOuterJoin($s, array('no','member'))->get('name')->byNo(1)->q());
		$this->assertEquals($t->get('name')->byNo(1)->q(), $t->leftOuterJoin($s, array('pk'=>'no','fk'=>'member'))->get('name')->byNo(1)->q());
		
		$this->assertEquals(at($t->leftOuterJoin($s->get('level'), array('no', 'member'))->get('name')->byNo(1)->q(), 0), 'name1');
		$this->assertEquals(at($t->leftOuterJoin($s->get('level'), array('no', 'member'))->get('name')->byNo(1)->q(), 1), '1');
		$this->assertEquals(at(at($t->leftOuterJoin($s->get('level'), array('no', 'member'))->get('name')->byNo(1)->q(), 'student_info'), 'level'), '1');
		$this->assertEquals(at($t->leftOuterJoin($s->byMember(1), array('no', 'member'))->get('zipcode')->byNo(1)->q(), 0), 'zipcode1');
		$this->assertNotEquals(at($t->leftOuterJoin($s->byNo(2), array('no', 'member'))->get('zipcode')->byNo(1)->q(), 0), 'zipcode1');
		$this->assertContains('zipcode2', at($t->leftOuterJoin($s->OrByMember(2), array('no', 'member'))->get('zipcode')->byNo(1)->ql(), 1));
		
		$this->assertNotEquals($t->leftOuterJoin($s->get('no'), array('no', 'member'))->get('*')->byNo(1)->q(),
							  $t->leftOuterJoin($s->get('no'), array('no', 'member'))->byNo(1)->q());
		
		$this->assertContains('zipcode1', $t->leftJoin($s->byMember(1), array('no', 'member'))->get('zipcode')->q());
		$this->assertContains('zipcode1', $t->leftJoin($s->nameAs('s')->byMember(1), array('no', 'member'))->get('zipcode')->q());
		$this->assertContains('zipcode1', $t->leftJoin($s->byMember(1)->nameAs('s'), array('no', 'member'))->get('zipcode')->q());
		
		$this->assertNotEquals(($t->innerJoin($s, array('no', 'member'))->get('zipcode')->byNo(5)->q()),
							   $t->leftOuterJoin($s, array('no', 'member'))->get('zipcode')->byNo(5)->q());
		$this->assertEquals($t->innerJoin($s, array('no', 'member'))->get('zipcode')->byNo(5)->q(),
						   $t->innerJoin($s, 'member_info.no = student_info.member')->get('zipcode')->byNo(5)->q());
							   				
		$this->assertNotEquals($ol->byNo(4)->q(), 
						   $ol->innerJoin($op->leftOuterJoin($p, array('product', 'no')), array('no', 'order'))->byNo(4)->q());
						   
		$data = $ol->leftOuterJoin($op->leftOuterJoin($p->get('l_category'), array('product', 'no'))->byProduct(1), array('no', 'order'))->byNo(3)->q();
		$this->assertEquals('l_category1',$data['product_list']['l_category']);
		
		
		$this->assertNotEquals($t->leftOuterJoin($s->get('no'), array('no', 'member'))->get('*')->byNo(1)->q(),
							  $t->leftOuterJoin($s->get('no')->nameAs('student'), array('no', 'member'))->get('*')->byNo(1)->q());
	}
	
	function testInsert() {
		$t = $this->member;	
		$data['date'] = 0;
		$data['ip'] = '172.31.1.1';
		$data['state'] = 1;
		$data['name'] = "\"string\" with 'quotation' and \\ ";
		$data['email'] = "test@test.com";
		$data['head'] = 1;
		$data['academy'] = 1;
		
		$testNo = $t->insert($data);
		$this->assertContains("\"string\" with 'quotation' and \\ ", $t->byNo($testNo)->q());
		$this->assertContains('172.31.1.1', $t->byNo($testNo)->q());
		$this->assertContains("test@test.com", $t->byNo($testNo)->q());
	}	
	
	function testUpdate() {
		$t = $this->member;
		
		$data['name'] = "test";
		$data['email'] = "test2@test.com";
		$t->byName('name1')->update($data);
		$this->assertContains("test2@test.com", $t->byNo(Q($t)->get('no')->byName('test')->getOne())->q());
		
		$t->U($data);
		$this->assertTrue(count($t->get('no')->byName('test')->QL()) > 1);
	}

	function testDelete(){
		$t = $this->member;
		
		$t->byName('name1')->delete();
		$this->assertNotInArray('name1', $t->byName('name1')->q());
		$this->assertContains('name2', $t->byName('name2')->q());
		$data = $t->q();
		$t->delete();
		$this->assertNotEquals($data, $t->q());
	}

	function testShortJoin() {
		$a = $this->member->join(
			Q('student_info'),
			array('no', 'member')
		)->createSelectQuery();

		$b = $this->member->join(
			'student_info',
			array('no', 'member')
		)->createSelectQuery();

		$this->assertEquals($a, $b);

		$a = $this->member->leftOuterJoin(
			Q('student_info'),
			array('no', 'member')
		)->createSelectQuery();

		$b = $this->member->leftOuterJoin(
			'student_info',
			array('no', 'member')
		)->createSelectQuery();

		$this->assertEquals($a, $b);
	}

    function testExecute() {
        $this->assertTrue($this->member->exec() instanceof PDOStatement);
    }
		
	function assertNotInArray($needle, array $haystack){
		if(is_array($haystack))
			return $this->assertFalse(in_array($needle, $haystack));
	}

	function createTestTable($tableNames, $schemaFile) {
		$tableNames = (array) $tableNames;
	
		$isTable = false;
		foreach($tableNames as $tableName) if($this->dbh->query("DESC $tableName"))
			$isTable = true;
		
		if(!$isTable)
			foreach($data = explode(';', join('', file($schemaFile))) as $record) 
				if(trim($record) != '')	$this->dbh->query($record);
		
	}
	
	function dropTable($list) {
		foreach($list as $tableName)
			$this->dbh->query("DROP TABLE $tableName");
	}
}
	
