<?php

/**
 * @author zmiller
 */
class Listings extends Service {
    
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
        $ListingsTable = new ListingsTable($this->m_oConnection);

        // Parse the not city array
        if(!empty($this->m_aInput['not_city'])) {
            $this->m_aInput['not_city'] = preg_split("/\r\n|\n|\r/", $this->m_aInput['not_city']);
        }
        
        // Parse the distances array
        $distances = array();
        if(!empty($this->m_aInput['distances'])) {
            $rows = preg_split("/\r\n|\n|\r/", $this->m_aInput['distances']);
            foreach($rows as $d) {
                $parts = explode("|", $d);
                if(sizeof($parts) == 3) {
                    $url = "http://maps.googleapis.com/maps/api/geocode/json?&address=".urlencode(trim($parts[1]));
                    $json = json_decode(file_get_contents($url), true);
                    if(isset($json['results'][0]['geometry']['location'])) {
                        $distances['distances_'.trim($parts[0])] = array(
                            'location' => $json['results'][0]['geometry']['location'], 
                            'min' => trim($parts[2])
                        );
                    }
                }
            }
        }
        
        $this->m_aInput['distances'] = $distances;
        $this->m_mData = $ListingsTable->select($this->m_aInput);
        return true;
    }
}
