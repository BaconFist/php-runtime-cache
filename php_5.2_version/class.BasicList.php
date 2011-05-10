<?php
/**
 * Description of BasicList
 *
 * Eine  Liste auf die per index, beginnend bei 0, zugegriffen werden kann.
 *
 * @author Thomas Klose
 */
class BasicList implements ArrayAccess, Iterator, Countable {

    private $ListItems;
    private $iterator;
    private $count;
    private $maxsize = false;
    private $allowDuplicates = true;
    private $ValueValidationClass = null;

    public function __construct() {
        $this->clear();
    }

    public function __destruct() {
        unset(
                $this->ListItems,
                $this->iterator,
                $this->count
        );
    }

    public function isMaxSizeReached() {
        return !((false === $this->getMaxsize()) || (count($this) < $this->getMaxsize()));
    }

    public function getMaxsize() {
        return $this->maxsize;
    }

    public function setMaxsize($maxsize = false) {
        if ((false === $maxsize) or (\is_integer($maxsize) && ($maxsize > 0))) {
            $this->maxsize = $maxsize;
        }
    }

    public function getHighestIndex(){
        return count($this)-1;
    }

    public function getFirstValue() {
        return $this[0];
    }

    public function getLastValue() {
        return $this[count($this) - 1];
    }

    final public function ReverseList() {
        $this->ListItems = array_reverse($this->ListItems);
    }

    public function getValueValidationClass() {
        return $this->ValueValidationClass;
    }

    /**
     * set the Type of this List;
     * if $ValueValidationClass is set to false then Validation is disabled (default)
     *
     * @param mixed $ValueValidationClass Classname or false
     */
    public function setValueValidationClass($ValueValidationClass=false) {
        $this->ValueValidationClass = (false !== $ValueValidationClass) ? $ValueValidationClass : null;
    }

    public function getAllowDuplicates() {
        return $this->allowDuplicates;
    }

    public function setAllowDuplicates($allowDuplicates) {
        $this->allowDuplicates = $allowDuplicates;
    }

    final public function getArray() {
        $tempArr = $this->ListItems;
        ksort($tempArr);
        return $tempArr;
    }

    protected function getQuickSortValue($offset) {
        return $this[$offset];
    }

    /**
     * sortiert eine Liste anhand des von 'getQuickSortValue()' zurückgegebenen Wertes
     * sortiert aufsteigend
     *
     * @return $this
     */
    final public function quickSort($descending = false) {
        if (0 < count($this)) {
            $current = 1;
            $stack[1]['low'] = 0;
            $stack[1]['high'] = count($this) - 1;

            do {
                $low = $stack[$current]['low'];
                $high = $stack[$current]['high'];
                $current--;

                do {
                    $low_offset = $low;
                    $high_offset = $high;
                    $current_value = $this->getQuickSortValue((int) ( ($low + $high) / 2 ));

                    // partion the array in two parts.
                    // left from $tmp are with smaller values,
                    // right from $tmp are with bigger ones
                    do {
                        while ($this->getQuickSortValue($low_offset) < $current_value) {
                            $low_offset++;
                        }

                        while ($current_value < $this->getQuickSortValue($high_offset)) {
                            $high_offset--;
                        }

                        // swap elements from the two sides
                        if ($low_offset <= $high_offset) {
                            $this->exchange($low_offset, $high_offset);

                            $low_offset++;
                            $high_offset--;
                        }
                    } while ($low_offset <= $high_offset);


                    if ($low_offset < $high) {
                        $current++;
                        $stack[$current]['low'] = $low_offset;
                        $stack[$current]['high'] = $high;
                    }
                    $high = $high_offset;
                } while ($low < $high);
            } while ($current != 0);
            if (true === $descending) {
                $this->ReverseList();
            }
        }
        return $this;
    }

    /**
     * gibt den index von value zurück
     *
     * @param mixed $value
     * @param boolean $strict
     * @return integer gibt den index zurück, wenn nicht gefunden -1
     */
    final public function IndexOf($value, $strict=true) {
        if ($this->ValueExists($value, $strict)) {
            if ($this->isValidValue($value)) {
                for ($i = 0; $i < count($this); $i++) {
                    if ($strict) {
                        if ($this[$i] === $value) {
                            return $i;
                        }
                    } else {
                        if ($this[$i] == $value) {
                            return $i;
                        }
                    }
                }
            }
        }
        return -1;
    }

    /**
     * Gibt true zurück wenn Value in der Liste ist, ansonsten false
     *
     * @param mixed $value
     * @param boolean $strict wenn true wird strict in der Liste gesucht
     * @return boolean
     */
    final public function ValueExists($value, $strict=true) {
        return in_array($value, $this->ListItems, $strict);
    }

    /**
     *  Verschiebt Eintrag $offset um $count Felder zum Listenanfang
     *
     * @param integer $offset  Index des zu verschiebenden Eintrags
     * @param integer $count=1 Anzahl der Felder um die der Eintrag verschoben werden soll
     * @return BasicList gibt $this zurück
     */
    final public function pull($offset, $count=1) {
        return $this->moove($offset, $offset - $count);
    }

    /**
     *  Verschiebt Eintrag $offset um $count Felder zum Listenende
     *
     * @param integer $offset  Index des zu verschiebenden Eintrags
     * @param integer $count=1 Anzahl der Felder um die der Eintrag verschoben werden soll
     * @return BasicList gibt $this zurück
     */
    final public function push($offset, $count=1) {
        return $this->moove($offset, $offset + $count);
    }

    /**
     * Verschiebt Eintrag $offsetSource an die stelle von $offsetTarget
     *
     * @param integer $offsetSource  index des Eintrags der verschoben werden soll
     * @param integer $offsetTarget  Zielindex an welche stelle der Eintrag verschoben werden soll
     * @return BasicList gibt $this zurück
     */
    final public function moove($offsetSource, $offsetTarget) {
        if (($offsetSource !== $offsetTarget) && $this->isValidOffset($offsetSource) && $this->isValidOffset($offsetTarget)) {
            $tempNode = $this->ListItems[$offsetSource];
            if ($offsetSource < $offsetTarget) {
                for ($i = $offsetSource; $i < $offsetTarget; $i++) {
                    $this->ListItems[$i] = $this->ListItems[$i + 1];
                }
            } else {
                for ($i = $offsetSource; $i > $offsetTarget; $i--) {
                    $this->ListItems[$i - 1] = $this->ListItems[$i];
                }
            }
            $this->ListItems[$offsetTarget] = $tempNode;
        }
        return $this;
    }

    /**
     *
     * @param integer $FirstExchangeOffset
     * @param integer $SecondExchangeOffset
     * @return BasicList gibt $this zurück
     */
    final public function exchange($FirstExchangeOffset, $SecondExchangeOffset) {
        if (($FirstExchangeOffset !== $SecondExchangeOffset) && $this->isValidOffset($FirstExchangeOffset) && $this->isValidOffset($SecondExchangeOffset)) {
            $tempNode = $this->ListItems[$FirstExchangeOffset];
            $this->ListItems[$FirstExchangeOffset] = $this->ListItems[$SecondExchangeOffset];
            $this->ListItems[$SecondExchangeOffset] = $tempNode;
        }
        return $this;
    }

    /**
     *
     * @param integer $offset
     * @return BasicList gibt $this zurück
     */
    final public function delete($offset) {
        if ($this->isValidOffset($offset)) {
            unset($this->ListItems[$offset]);
            $this->ListItems = array_values($this->ListItems);
            $this->count--;
            if ($this->count < 0) {
                $this->count = 0;
            }
        }
        return $this;
    }

    /**
     *  erhöht den internetn counter um 1
     *
     * @return integer gibt den neuen wert vom internen counter zurück
     */
    final private function grow() {
        return $this->count++;
    }

    /**
     * vermindert den internen Counter um 1 und löscht das letzte element aus der Liste
     *
     * Pädant zu array_pop
     *
     * @return mixed gibt den neuen gelöschten Wert zurück
     */
    final public function pop() {
        $deletedElement = $this->getLastValue();
        $this->delete($this->getHighestIndex());
        return $deletedElement;
    }

    /**
     * vermindert den internen Counter um 1 und löscht das erste element aus der Liste
     *
     * Pädant zu array_shift
     *
     * @return mixed gibt den neuen gelöschten Wert zurück
     */
    final public function shift() {
        $deletedElement = $this->getFirstValue();
        $this->delete(0);
        return $deletedElement;
    }

    /**
     *  fügt den Eintrag $value an die Stelle mit dem Index $offset ein
     *
     * @param mixed $newValue Der neue Eintrag
     * @param integer $offset Index an dem der neue Eintrag eingefügt wird
     * @return mixed gibt bei erfolg den neuen eintrag zurück, andernfalls null
     */
    public function insert($newValue, $offset) {
        if ($this->isValidOffset($offset) && $this->add($newValue) >= 0) {
            $this->moove($this->count - 1, $offset);
            return $offset;
        } else {
            return -1;
        }
    }

    final public function getFirst() {
        return $this->rewind()->current();
    }

    /**
     * fügt den Eintrag $value am Ende der Liste ein
     *
     * @param mixed $newValue Der neue Eintrag
     * @return integer gibt bei erfolg den neuen index zurück, andernfalls -1
     */
    public function add($newValue) {
        return $this->offsetSet(null, $newValue);
    }

    /**
     * 	Fügt die Inhalte einr Lsite vom gleichen typ hinzu
     *
     * @param TBasciList $newList liste die hinzugefügt wirs
     * @return mixed false bei einem fehler, ansonsten $this
     */
    public function addList(BasicList $newList) {
        $ListClassname = get_class($this);
        if (!($newList instanceof $ListClassname)) {
            $Type = (is_object($newList)) ? get_class($newList) : gettype($newList);
            throw new Exception('Argument 1 ' . $Type . 'given, expecting instance of ' . $ListClassname . ' or one of its parents.');
        }
        $this->addArray($newList->getArray());
        return $this;
    }

    /**
     * Fügt ein Array der Liste hinzu
     * @param array $newArray
     * @return BasicList
     */
    public function addArray(array $newArray) {
        foreach ($newArray AS $newValue) {
            $this->add($newValue);
        }
        return $this;
    }

    /**
     * 	Leert die komplette Liste
     *
     * @return BasicList $this
     */
    final public function clear() {
        $this->ListItems = array();
        $this->count = 0;
        $this->rewind();
        return $this;
    }

    /**
     * Überprüft ob der mit $value übergebene wert in die Liste geschrieben werden darf
     *
     * anmerkung:
     *      Diese Funktion gibt in der Basisklasse immer true zurück,
     *      und soll lediglich die Typisierung abgeleiteter Listen ermöglichen.
     *
     * @param mixed $value zu Prüfender Wert
     * @return boolean true im Erfolgsfall, andernfals false
     */
    protected function isValidValue($value) {
        $ValueValidationClass = $this->getValueValidationClass();
        if (is_null($ValueValidationClass)) {
            return true;
        } else {
            return ($value instanceof $ValueValidationClass);
        }
    }

    public function testValue($value) {
        return $this->isValidValue($value);
    }

    /**
     * Überprüft ob es sich bei dem mit $offset übergebenen Wert um einen gültigen Listenindex handelt
     *
     * @param integer $offset zu Überprüfender wert
     * @return boolean true im Erfolgsfall, andernfals wird eine Fehlermeldung ausgegeben
     */
    final protected function isValidOffset($offset) {
        if (!is_int($offset)) {
            throw new Exception("(" . gettype($offset) . ") '" . $offset . "' is not a valid integer value");
        }
        if (!((-1 < $offset) && ($offset < $this->count))) {
            throw new Exception('List index out of bounds (' . $offset . ')');
        }
        return true;
    }

    final public function implode($glue) {
        return implode($glue, $this->getArray());
    }

    /* interfaces -- begin -- */

    /* ArrayAccess -- begin -- */

    final public function offsetExists($offset) {
        return isset($this->ListItems[$offset]);
    }

    final public function offsetGet($offset) {
        return $this->isValidOffset($offset) ? $this->ListItems[$offset] : null;
    }

    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            if ((!$this->isMaxSizeReached()) && $this->isValidValue($value) && ($this->getAllowDuplicates() || !$this->ValueExists($value))) {
                $this->grow();
                $this->ListItems[$this->count - 1] = $value;
                return $this->count - 1;
            }
            return -1;
        } elseif ($this->isValidOffset($offset) and ($this->isValidValue($value))) {
            $this->ListItems[$offset] = $value;
            return $offset;
        } else {
            return -1;
        }
    }

    final public function offsetUnset($offset) {
        return $this->delete($offset);
    }

    /* ArrayAccess -- end -- */

    final public function current() {
        return $this[$this->iterator];
    }

    final public function next() {
        $this->iterator++;
    }

    final public function key() {
        return $this->iterator;
    }

    final public function valid() {
        return isset($this[$this->iterator]);
    }

    final public function rewind() {
        $this->iterator = 0;
        return $this;
    }

    /* Iterator -- end -- */


    /* Countable -- begin -- */

    final public function Count() {
        return $this->count;
    }

    /* Countable -- end -- */

    /* interfaces -- end -- */
}