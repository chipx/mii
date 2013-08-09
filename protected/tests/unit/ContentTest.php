<?php
/**
 * Created by JetBrains PhpStorm.
 * User: chipx
 * Date: 25.07.13
 * Time: 13:04
 * To change this template use File | Settings | File Templates.
 */

class ContentTest extends CDbTestCase {

    protected $fixtures = [
        'content' => '\Lib\Models\Db\Content',
        'content_fields' => '\Lib\Models\Db\ContentFields',
    ];

    public function testCreate()
    {
        $newContent = new \Lib\Models\Db\Content();
        $newContent->title = 'Hello world';
        $newContent->body = 'Test body';
        $newContent->state = \Lib\Models\Db\Content::STATE_PROTECTED;
        $this->assertTrue($newContent->save());

        $this->assertTrue($newContent->id > 0);
        $this->assertEquals('Hello world', $newContent->title);

        $created = strtotime($newContent->created);
        $this->assertTrue(time() >= $created);

        $updated = strtotime($newContent->updated);
        $this->assertTrue(time() >= $updated);

        $this->assertEquals($newContent->created, $newContent->updated);
    }

    public function testExtendField()
    {
        $content = \Lib\Models\Db\Content::model()->find(1);
        $this->assertInstanceOf('\Lib\Models\Db\Content', $content);
        $this->assertEquals('dsfsdgsfwer  fgsfgsfg sfxb xvbxvberwretwer', $content->fields->icon);
    }
}
