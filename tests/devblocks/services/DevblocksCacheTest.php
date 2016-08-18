<?php
class DevblocksCacheTest extends PHPUnit_Framework_TestCase
{
    final public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }
    
    public function testCachePersistSave()
    {
        $cache = DevblocksPlatform::getCacheService();
        
        $expected = true;
        $actual = $cache->save('test123', 'test.cache');
        
        $this->assertEquals($expected, $actual);
    }
    
    /**
     * @depends testCachePersistSave
     */
    public function testCachePersistRead()
    {
        $cache = DevblocksPlatform::getCacheService();
        
        $expected = 'test123';
        $actual = $cache->load('test.cache');
        
        $this->assertEquals($expected, $actual);
    }
    
    /**
     * @depends testCachePersistRead
     */
    public function testCachePersistRemove()
    {
        $cache = DevblocksPlatform::getCacheService();
        
        $expected = true;
        $actual = $cache->remove('test.cache');
        
        $this->assertEquals($expected, $actual);
    }
    
    public function testCacheLocalSave()
    {
        $cache = DevblocksPlatform::getCacheService();
        
        $expected = true;
        $actual = $cache->save('this is some data', 'test.cache.local', array(), 0, true);
        
        $this->assertEquals($expected, $actual);
    }
    
    /**
     * @depends testCacheLocalSave
     */
    public function testCacheLocalRead()
    {
        $cache = DevblocksPlatform::getCacheService();
        
        $expected = 'this is some data';
        $actual = $cache->load('test.cache.local', false, true);
        
        $this->assertEquals($expected, $actual);
    }
    
    /**
     * @depends testCacheLocalRead
     */
    public function testCacheLocalRemove()
    {
        $cache = DevblocksPlatform::getCacheService();
        
        $expected = true;
        $actual = $cache->remove('test.cache.local', true);
        
        $this->assertEquals($expected, $actual);
    }
}
