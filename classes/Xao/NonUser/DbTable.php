<?php
class       Xao_NonUser_DbTable 
extends     Xao_Root 
implements  Iterator
{

    protected $arrTbl;
    protected $intRowCursor = 0;

    public function __construct(&$arrTbl) {
        if(!is_array($arrTbl)) {
            $this->SetGoodToGo(false);
            $this->Xao_Root("DbTable is expecting a 2 dimensional array " .
                "(table). A ".gettype($arrTbl)." was received instead.");
            return;
        }
        if(count($arrTbl)) {
            if(!array_key_exists(0,$arrTbl)) {
                $this->SetGoodToGo(false);
                $this->Xao_Root("DbTable is expecting a 2 dimensional array " .
                    "of which the first dimension is numeric. The supplied" .
                    " argument does not seem to contain a numeric index.");
                return;
            }
            $arrFirstRow = $arrTbl[0];
            if(!is_array($arrFirstRow)) {
                $this->SetGoodToGo(false);
                $this->Xao_Root("DbTable is expecting a 2 dimensional array. " .
                    "The supplied argument seems to only have one dimension.");
                return;
            }
        }
        $this->arrTbl = $arrTbl;
    }
    
    public function key() {
        return $this->intRowCursor;
    }
    
    public function SetToFirst() {
        if(!$this->blnGoodToGo()) return false;
        $this->intRowCursor = 0;
    }
    
    public function rewind() {
        $this->SetToFirst();
    }

    public function SetToLast() {
        if(!$this->blnGoodToGo()) return false;
        $this->intRowCursor = count($this->arrTbl) - 1;
    }

    public function wind() {
        $this->SetToLast();
    }

    public function rowCurrent() {
        if(!$this->blnGoodToGo()) return false;
        if($this->intRowCursor < 0) return false;
        return $this->arrTbl[$this->intRowCursor];
    }
    
    public function current() {
        return $this->rowCurrent();
    }
    
    function rowNextRow() {
        if(!$this->blnGoodToGo()) return false;
        if($this->intRowCursor >= count($this->arrTbl)) return false;
        return $this->arrTbl[++$this->intRowCursor];
    }
    
    public function next() {
        $this->intRowCursor++;
    }
    
    function rowPreviousRow() {
        if(!$this->blnGoodToGo()) return false;
        $this->intRowCursor--;
        if($this->intRowCursor < 0) return false;
        return $this->arrTbl[$this->intRowCursor];
    }
    
    public function previous() {
        $this->intRowCursor--;
    }

    function rowFirst() {
        if(!$this->blnGoodToGo()) return false;
        return $this->arrTbl[0];
    }
    
    function rowLast() {
        if(!$this->blnGoodToGo()) return false;
        return $this->arrTbl[count($this->arrTbl) - 1];
    }
    
    function arrGetData() {
        return $this->arrTbl;
    }
    
    public function get() {
        return arrGetData();
    }
    
    public function valid() {
        return $this->intRowCursor < count($this->arrTbl) 
            && $this->intRowCursor >= 0;
    }
}
