import json
import requests
import mysql.connector
import conf

max_price = 350000
cities = ['Denver', 'Westminster', 'Broomfield', 'Arvada', 'Commerce City', 'Lakewood', 'Thornton', 'Northglenn', 'Aurora',
          'Golden', 'Wheat Ridge']

connection = mysql.connector.connect(user=conf.db_user, password=conf.db_passwd, host=conf.db_addr, database=conf.db_name)

def getListings(page, city):

    headers = {
        'X-Requested-With': 'XMLHttpRequest'
    }

    data = {
        'action': 'lazy_search',
        'idx_data[limit]': '25',
        'idx_data[search][feed]': '',
        'idx_data[search][is_results_page]': page,
        'idx_data[search][prefix]': 'search-homes',
        'idx_data[search][primary_criteria_map][city][]': city,
        'idx_data[search][search_type][]': 'city',
        'idx_data[search][secondary_criteria][features][]': 'Active',
        'idx_data[search][secondary_criteria][order]': 'days_on_market_count asc',
        'idx_data[search][secondary_criteria][page]': page,
        'idx_data[search][secondary_criteria][price][max_price]': max_price,
        'idx_data[search][secondary_criteria][price][min_price]': '',
        'initial': 'true'
    }

    # Make the request, terminate if nothing is returned
    r = requests.post('http://coloradohomeagents.net/admin/front/', headers=headers, data=data)
    response = json.loads(r.text)
    return response['json_listings']

def insertListing(listing, connection):
    sql = '''
            REPLACE INTO listings
            (mls_id, city, state, zip, address, year, sq_ft, beds, baths, price, price_per_sqft, taxes, floors,
                elementary_school, middle_school, high_school, neighborhood, remarks, primary_photo, date_listed, lat, lng, url)
            VALUES
            ("{0}", "{1}", "{2}", "{3}", "{4}", "{5}", "{6}", "{7}", "{8}", "{9}", "{10}", "{11}", "{12}", "{13}",
                "{14}", "{15}", "{16}", {17}, "{18}", "{19}", "{20}", "{21}", "{22}");
        '''.format(listing['mls_id'], listing['city'], listing['state'], listing['zip'], listing['full_address'],
           listing['year'], listing["sq_ft"], listing['beds'], listing['baths_total'], listing['price'],
                   listing['price_per_sqft'], listing["non_mapped_fields"]['Taxes'], listing['floors'],
                   listing['schools']['e']['name'], listing['schools']['m']['name'], listing['schools']['h']['name'],
                   listing['neighborhood'], json.dumps(listing['remarks'].encode('ascii', 'ignore')),
                   listing['primary_photo'], listing['date_listed'], listing['lat'], listing['lng'], listing['url'])

    connection.cursor().execute(sql)
    connection.commit()

def insertSchools(schools, connection):
    sql = '''
            INSERT IGNORE INTO school_ratings
            (name, level, rating)
            VALUES
            ('{0}', 'elementary', '{1}'),
            ('{2}', 'middle', '{3}'),
            ('{4}', 'high', '{5}');
        '''.format(schools['e']['name'], schools['e']['rating'],
                   schools['m']['name'], schools['m']['rating'],
                   schools['h']['name'], schools['h']['rating'])

    connection.cursor().execute(sql)
    connection.commit()

def getSchools(listing):
    default = {'name': '-', 'rating': '-'}
    try:
        p = {
            'lat': listing['lat'],
            'lon': listing['lng'],
            'level': ''
        }

        retval = {}
        r = requests.get('http://www.greatschools.org/geo/boundary/ajax/getAssignedSchoolByLocation.json', params=p)
        schools = json.loads(r.text)
        for school in schools['results']:
            retval[school['level']]= school['schools'][0] if len(school['schools']) > 0 else default

        return retval

    except Exception:
        return {
            'e': default,
            'm': default,
            'h': default
        }

for city in cities:
    page = 1
    while True:
        print "----------------------" + "\n" + "Page: " + str(page) + "\n" + "----------------------"
        # Select the page
        listings = getListings(page, city)
        if len(listings) == 0:
            break

        # Parse and load the information
        for i in range(0, len(listings)):
            print listings[i]['full_address']
            listings[i]['schools'] = getSchools(listings[i])
            insertListing(listings[i], connection)
            insertSchools(listings[i]['schools'], connection)

        page = page + 1

connection.close()