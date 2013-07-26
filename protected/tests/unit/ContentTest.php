<?php
/**
 * Created by JetBrains PhpStorm.
 * User: chipx
 * Date: 25.07.13
 * Time: 13:04
 * To change this template use File | Settings | File Templates.
 */

class ContentTest extends CDbTestCase {

    public function testCreate()
    {
        $newContent = new \application\models\Content();
        $newContent->title = 'Hello world';
        $newContent->body = 'Test body';
        $newContent->state = \application\models\Content::STATE_PROTECTED;
        $this->assertTrue($newContent->save());

        $this->assertTrue($newContent->id > 0);
        $this->assertEquals('Hello world', $newContent->title);

        $created = strtotime($newContent->created);
        $this->assertTrue(time() >= $created);

        $updated = strtotime($newContent->updated);
        $this->assertTrue(time() >= $updated);

        $this->assertEquals($newContent->created, $newContent->updated);
    }
}
