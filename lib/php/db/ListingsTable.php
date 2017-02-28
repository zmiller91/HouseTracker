<?php

/**
 * @author zmiller
 */
class ListingsTable extends BaseTable {
    
    public function select($input) {
        
        $year = !empty($input['min_year']) ? "AND year >= ".$input['min_year'] : "";
        $ms = !empty($input['ms']) ? "AND ms.rating >= ".$input['ms'] : "";
        $es = !empty($input['es']) ? "AND es.rating >= ".$input['es'] : "";
        $hs = !empty($input['hs']) ? "AND hs.rating >= ".$input['hs'] : "";
        $minSqft = !empty($input['min_sqft']) ? "AND sq_ft >= ".$input['min_sqft'] : "";
        $minPrice = !empty($input['min_price']) ? "AND price >= ".$input['min_price'] : "";
        $minBeds = !empty($input['min_beds']) ? "AND beds >= ".$input['min_beds'] : "";
        $minBaths = !empty($input['min_baths']) ? "AND baths >= ".$input['min_baths'] : "";
        $maxPrice = !empty($input['max_price']) ? "AND price <= ".$input['max_price'] : "";
        
        $notCities = !empty($input['not_city']) ? "AND city NOT IN ('".implode("','", $input['not_city'])."')" : "";
        $distanceCriteria = !empty($input['distances']) ? ", ".$this->getDistancesCriteria($input['distances']) : "";
        $distanceFilter = !empty($input['distances']) ? $this->getDistanceFilter($input['distances']) : "";
        $typeFilter = !empty($input['type']) ? "AND type IN ('".implode("','", $input['type'])."')" : "";
                
        $sql = 
<<<EOD
    select * 
    from (
        select 
        mls_id, primary_photo, city, address, taxes, es.rating as es_rating, 
        ms.rating as ms_rating, hs.rating as hs_rating, year, beds, baths, 
        price, sq_ft, price_per_sqft, date_listed, neighborhood, remarks, url, type
                
        $distanceCriteria

        from listings
        left join school_ratings as es on elementary_school = es.name and es.level = 'elementary'
        left join school_ratings as ms on middle_school = ms.name and ms.level = 'middle'
        left join school_ratings as hs on high_school = hs.name and hs.level = 'high'

        where elementary_school != '-'
        and middle_school != '-'
        and high_school != '-'
        and primary_photo != 'https://s3.amazonaws.com/irecdn/themes/common/no-image-small.jpg'
                
         $es $ms $hs $year $minSqft $minPrice $minBeds $minBaths $maxPrice 
         $notCities $typeFilter
    )  a

    where 1 = 1
    $distanceFilter       
    ORDER BY date_listed DESC
EOD;
        
        return $this->execute($sql);
    }
    
    public function getDistinctTypes() {
        $sql = 
<<<EOD
            SELECT DISTINCT type
            FROM listings;
EOD;
        return $this->execute($sql);
    }
    
    private function getDistancesCriteria($distances) {
        
        $distanceCriteria = array();
        foreach($distances as $k => $d) {
            $lat = $d['location']['lat'];
            $lng = $d['location']['lng'];
            array_push($distanceCriteria, "3956 * 2 * ASIN(SQRT( POWER(SIN(($lat - abs(lat)) * pi()/180 / 2),2) + COS($lat * pi()/180 ) * COS(abs(lat) *  pi()/180) * POWER(SIN(($lng - lng) *  pi()/180 / 2), 2) )) as $k");
        }
        
        return implode(",", $distanceCriteria);
    }
    
    private function getDistanceFilter($distances) {
        
        $filterCriteria = array();
        foreach($distances as $k => $d) {
            $min = $d['min'];
            array_push($filterCriteria, " AND $k <= $min ");
        }
        
        return implode("", $filterCriteria);
    }
    
    
    
}
