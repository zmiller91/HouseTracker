<?php

/**
 * @author zmiller
 */
class HouseTypes extends Service {
    
    protected function allowableMethods() {
        return array(self::GET);
    }

    protected function authorize() {
        return true;
    }

    protected function validate() {
        return true;
    }

    protected function get() {
        
        $retval = array();
        $ListingsTable = new ListingsTable($this->m_oConnection);
        $types = $ListingsTable->getDistinctTypes();
        foreach($types as $t) {
            array_push($retval, $t['type']);
        }
        
        $this->m_mData = $retval;
        return true;
    }
}
