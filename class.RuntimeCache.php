<?php

class RuntimeCache {
    const CT_LRU = 0;
    const CT_LTU = 1;
    const CT_FIFO = 2;

    static private $CacheType = 0;
    static private $CacheStack = array();

    static public function setCacheType($type) {
        switch ($type) {
            case self::CT_LRU:
            case self::CT_LTU:
            case self::CT_FIFO:
                self::$CacheType = $type;
        }
    }

    static public function getCacheType() {
        return self::$CacheType;
    }

    static function get($cacheId, $ResourceReference) {
        if (self::has($cacheId, $ResourceReference)) {
            return self::$CacheStack[$cacheId][$ResourceReference];
        } else {
            return false;
        }
    }

    static function has($cacheId, $ResourceReference) {
        return (isset(self::$CacheStack[$cacheId]) && isset(self::$CacheStack[$cacheId][$ResourceReference]));
    }

    static function set($cacheId, $ResourceReference, $value) {
        if (!isset(self::$CacheStack[$cacheId])) {
            self::$CacheStack[$cacheId] = new RuntimeCacheStack();
        }
        self::$CacheStack[$cacheId][$ResourceReference] = $value;
    }

}

class RuntimeCacheStack implements Countable, ArrayAccess, Iterator {

    private $items = array();
    private $maxCount = 0;

    public function __construct() {
        $this->items = array();
        rewind($this);
    }

    public function shiftLowestItem() {
        $cachedValue = null;
        $max_count = $this->getMaxCount();
        $count = count($this->items);
        if (($count > 0) && ($max_count > 0) && ($max_count < $count)) {
            switch (RuntimeCache::getCacheType()) {
                case RuntimeCache::CT_LRU:
                    $id = $this->getIndexOfLowest_LRU();
                    break;
                case RuntimeCache::CT_LTU:
                    $id = $this->getIndexOfLowest_LTU();
                    break;
                case RuntimeCache::CT_FIFO:
                    $id = $this->getIndexOfLowest_FIFO();
                    break;
                default:
                    $id = false;
                    break;
            }
            $RCR = $this->items[$offset];
            $cachedValue = (is_object($RCR) && ($RCR instanceof RuntimeCacheRessource)) ? $RCR->getData() : null;
            unset($RCR);
            if (false !== $id) {
                unset($this->items[$id]);
            }
        }
        return $cachedValue;
    }

    public function getMaxCount() {
        return $this->maxCount;
    }

    public function setMaxCount($maxCount) {
        $this->maxCount = (int) $maxCount;
    }

    private function getIndexOfLowest_LRU() {
        $low = null;
        $lowestId = false;
        foreach ($this->items as $id => $value) {
            /* @var $value RuntimeCacheRessource */
            $c_low = $value->getCount_access();
            if (is_null($low) or ($c_low < $low)) {
                $lowestId = $Id;
            }
        }
        return $lowestId;
    }

    private function getIndexOfLowest_LTU() {
        $low = null;
        $lowestId = false;
        foreach ($this->items as $id => $value) {
            /* @var $value RuntimeCacheRessource */
            $c_low = $value->getLast_access_time();
            if (is_null($low) or ($c_low < $low)) {
                $lowestId = $Id;
            }
        }
        return $lowestId;
    }

    private function getIndexOfLowest_FIFO() {
        $low = null;
        $lowestId = false;
        foreach ($this->items as $id => $value) {
            /* @var $value RuntimeCacheRessource */
            $c_low = $value->getCr_time();
            if (is_null($low) or ($c_low < $low)) {
                $lowestId = $Id;
            }
        }
        return $lowestId;
    }

    public function offsetExists($offset) {
        $offset = $this->getIndexKey($offset);
        return isset($this->items[$offset]);
    }

    public function offsetGet($offset) {
        $offset = $this->getIndexKey($offset);
        $RCR = $this->items[$offset];
        if (is_object($RCR) && ($RCR instanceof RuntimeCacheRessource)) {
            return $RCR->getData();
        } else {
            return null;
        }
    }

    public function offsetSet($offset, $value) {
        $offset = $this->getIndexKey($offset);
        $this->items[$offset] = new RuntimeCacheRessource($value);
    }

    public function offsetUnset($offset) {
        $offset = $this->getIndexKey($offset);
        unset($this->items[$offset]);
    }

    public function count() {
        return count($this->items);
    }

    public function current() {
        return current($this->items);
    }

    public function key() {
        return key($this->items);
    }

    public function next() {
        return next($this->items);
    }

    public function rewind() {
        return rewind($this->items);
    }

    public function valid() {
        $cur = current($this->items);
        return (is_object($cur) && ($cur instanceof RuntimeCacheRessource));
    }

    private function getIndexKey($var) {
        return (is_object($var)) ? spl_object_hash($var) : (is_array($var)) ? sha1(serialize($var)) : sha1((string) $var);
    }

}

class RuntimeCacheRessource {

    private $data = null;
    private $cr_time = 0;
    private $last_access_time = 0;
    private $count_access = 0;

    public function __construct($data) {
        $this->setData($data);
    }

    public function getData() {
        $this->incCount_access();
        $this->setLast_access_time();
        return $this->data;
    }

    public function setData($data) {
        $this->data = $data;
        $this->setLast_access_time();
        $this->setCr_time();
        $this->resetCount_access();
    }

    public function getCr_time() {
        return $this->cr_time;
    }

    private function setCr_time() {
        $this->cr_time = time() + microtime(true);
    }

    public function getLast_access_time() {
        return $this->last_access_time;
    }

    public function setLast_access_time() {
        $this->last_access_time = time() + microtime(true);
    }

    public function getCount_access() {
        return $this->count_access;
    }

    public function incCount_access() {
        $this->count_access++;
    }

    public function resetCount_access() {
        $this->count_access = 0;
    }

}
?>