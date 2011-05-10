<?php
require_once 'class.GenericList.php';
/**
 * Description of Cache
 *
 * how to use
 *
 * get a cached Value => Cache::YourCache($identifier);
 * set a Value to cache => Cache::YourCache($identifier, $content);
 * get your cache => Cache::YourCache();
 * set Maximum Cachesize => Cache::getCacheById('YourCache')->setMaxsize($size); //$size integer for maxsize of false to deactivate limitation.
 *
 * $identifier value to identify a cached value [exp: the parameters of a method]
 * $content value to cached. [the generated object / string / content ...]
 *
 *
 * @example:
 * Cache::test(array('foo','bar'),'content');
 * $cachedValue = Cache::test(array('foo','bar'));
 * now $cachedValue contains 'content'
 *
 * @author Klose Thomas 
 */
class RuntimeCache {
    /**
     * LFU (Least Frequently Used): replaces the least often used entry.
     * Default Cachetype (could be changed with Cache::setCacheTypeDefault($type)
     */
    const TYPE_LFU = 0;

    /**
     * LRU (Least Recently Used): replaces the least recently used entry.
     */
    const TYPE_LRU = 1;
    /**
     * FIFO (First In First Out): replaces the oldest entry.
     */
    const TYPE_FIFO = 2;

    static private $CacheTypeList = null;
    static private $CacheTypeDefault = 0;
    static private $CacheStack = array();

    static public function newCache($id, $forceOverwriteExisting = false) {
        $hasCache = self::hasCache($id);
        if (!$hasCache || ($hasCache && $forceOverwriteExisting)) {
            $cacheId = self::generateCacheId($id);
            self::$CacheStack[$cacheId] = new CacheStack();
        }
    }

    static public function deleteCache($id) {
        if (self::hasCache($id)) {
            unset(self::$CacheStack[self::generateCacheId($id)]);
        }
    }

    static public function hasCache($id) {
        return array_key_exists(self::generateCacheId($id), self::$CacheStack);
    }

    static private function generateCacheId($id) {
        return md5($id);
    }

    /**
     *
     * @param string $id
     * @return CacheStack
     */
    static private function getCacheById($id) {
        if (self::hasCache($id)) {
            return self::$CacheStack[self::generateCacheId($id)];
        } else {
            self::newCache($id, true);
            return self::getCacheById($id);
        }
    }

    static public function getCacheType($id) {
        if (self::hasCache($id)) {
            return self::getCacheById($id)->getCacheType();
        }
        return false;
    }

    static public function setCacheType($id, $type = 0) {
        if (self::isValidCacheType($type) && self::hasCache($id)) {
            $cache = self::getCacheById($id);
            $cache->setCacheType($type);
            $cache->quickSort();
            return true;
        }
        return false;
    }

    static public function isInCache($id, $identifier) {
        return (false !== self::getCacheById($id)->indexOfIdentifier($identifier));
    }

    static public function getCacheTypeDefault() {
        return self::$CacheTypeDefault;
    }

    static public function setCacheTypeDefault($type = 0) {
        if (self::isValidCacheType($type)) {
            self::$CacheTypeDefault = $type;
            return true;
        }
        return false;
    }

    static private function isValidCacheType($type) {
        if (is_null(self::$CacheTypeList)) {
            $RC = new ReflectionClass('RuntimeCache');
            $constants = $RC->getConstants();
            unset($RC);
            self::$CacheTypeList = 0;
            foreach ($constants as $coinstant_key => $constant_value) {
                if ((0 === strpos($coinstant_key, 'TYPE_')) && (is_integer($constant_value))) {
                    self::$CacheTypeList = self::$CacheTypeList ^ $constant_value;
                }
            }
        }
        $valid = (self::$CacheTypeList ^ $type);
        return $valid;
    }

    public static function setToCache($id, $identifier, $content) {
        self::getCacheById($id)->getByIdentifier($identifier)->setContent($content);
    }

    public static function getFromCache($id, $identifier) {
            return self::getCacheById($id)->getByIdentifier($identifier)->getContent();
    }

    public function __unset($id) {
        self::deleteCache($id);
    }

}

class CacheStack extends GenericList {

    private $CacheType;

    public function getCacheType() {
        return $this->CacheType;
    }

    public function setCacheType($CacheType) {
        $this->CacheType = $CacheType;
    }

    protected function getQuickSortValue($offset) {
        $CacheItem = parent::getQuickSortValue($offset);
        /* @var $CacheItem CacheItem */
        switch ($this->getCacheType()) {
            case Cache::TYPE_LFU:
                $value = $CacheItem->getCountUsed();
                break;
            case Cache::TYPE_LRU:
                $value = $CacheItem->getLastRecentlyUsed();
                break;
            case Cache::TYPE_FIFO:
                $value = $CacheItem->getTimeCreated();
                break;
            default:
                $value = $CacheItem;
                break;
        }
        return $value;
    }

    /**
     * 
     * @param mixed $identifier
     * @return CacheItem creates new Item on missmatch
     */
    public function getByIdentifier($identifier, $forceCreateNew = true) {
        $this->indexOfIdentifier($identifier, $match);
        if ($forceCreateNew && !$match) {
            $index = $this->add(new CacheItem($identifier));
            $match = $this[$index];
        }
        return $match;
    }

    public function indexOfIdentifier($identifier, &$match = null) {
        $matchedIndex = false;
        for ($index = 0, $count = count($this), $match = false; $index < $count && !$match; $index++) {
            if ($this[$index]->getIdentifier() === $identifier) {
                $match = $this[$index];
                $matchedIndex = $index;
            }
        }
        return $matchedIndex;
    }

    public function indexOfLowestLFU(&$match = null) {
        $lowerLFU = false;
        $lowerIndex = false;
        for ($index = 0, $count = count($this); $index < $count; $index++) {
            if ((false === $lowerLFU) || ($lowerLFU > $this[$index]->getCountUsed())) {
                $lowerIndex = $index;
            }
        }
        $match = $this[$lowerIndex];
        return $lowerIndex;
    }

    public function indexOfLowestLRU(&$match = null) {
        $lowerLRU = false;
        $lowerIndex = false;
        for ($index = 0, $count = count($this); $index < $count; $index++) {
            if ((false === $lowerLRU) || ($lowerLRU > $this[$index]->getLastRecentlyUsed())) {
                $lowerIndex = $index;
            }
        }
        $match = $this[$lowerIndex];
        return $lowerIndex;
    }

    public function indexOfLowestFIFO(&$match = null) {
        $lowerFIFO = false;
        $lowerIndex = false;
        for ($index = 0, $count = count($this); $index < $count; $index++) {
            if ((false === $lowerFIFO) || ($lowerFIFO > $this[$index]->getTimeCreated())) {
                $lowerIndex = $index;
            }
        }
        $match = $this[$lowerIndex];
        return $lowerIndex;
    }

    public function offsetSet($offset, $value) {
        while ($this->isMaxSizeReached()) {
            $this->deleteOverfloatingCacheValue();
        }
        return parent::offsetSet($offset, $value);
    }

    private function deleteOverfloatingCacheValue() {
        switch ($this->getCacheType()) {
            case Cache::TYPE_LFU:
                return $this->delete($this->indexOfLowestLFU());
                break;
            case Cache::TYPE_LRU:
                return $this->delete($this->indexOfLowestLRU());
                break;
            case Cache::TYPE_FIFO:
                return $this->delete($this->indexOfLowestFIFO());
                break;
        }
        return false;
    }

    public function __construct() {
        parent::__construct(CacheItem::getClassName());
        $this->setAllowDuplicates(false);
        $this->setCacheType(Cache::getCacheTypeDefault());
    }

}

class CacheItem {

    private $TimeCreated = 0;
    private $LastRecentlyUsed = 0;
    private $CountUsed = 0;
    private $Content = null;
    private $identifier = null;

    static public function getClassName() {
        return 'CacheItem';
    }

    public function __construct($identifier, $Content = null) {
        $this->setTimeCreated();
        $this->setIdentifier($identifier);
        $this->setContent($Content);
    }

    public function getContent() {
        $this->setLastRecentlyUsed();
        $this->incrementCountUsed();
        return $this->Content;
    }

    public function setContent($Content) {
        $this->setLastRecentlyUsed();
        $this->Content = $Content;
    }

    public function getTimeCreated() {
        return $this->TimeCreated;
    }

    private function setTimeCreated() {
        $this->TimeCreated = time();
    }

    public function getLastRecentlyUsed() {
        return $this->LastRecentlyUsed;
    }

    private function setLastRecentlyUsed() {
        $this->LastRecentlyUsed = time();
    }

    public function getCountUsed() {
        return $this->CountUsed;
    }

    private function incrementCountUsed() {
        $this->CountUsed++;
    }

    public function getIdentifier() {
        return $this->identifier;
    }

    public function setIdentifier($identifier) {
        $this->identifier = $identifier;
    }

}