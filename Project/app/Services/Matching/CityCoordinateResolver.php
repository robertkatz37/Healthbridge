<?php

namespace App\Services\Matching;

/**
 * Resolves an approximate lat/lng for a city/state pair when neither
 * side of a match has explicit coordinates on file — this is what makes
 * the Distance factor and the Distance filter actually functional for
 * the common case, rather than always falling back to a flat location
 * tier score (which the Phase 12 delivery originally did, and which is
 * why the Distance filter had no real effect: distance_miles was always
 * null, and a null distance never fails a "max distance" filter).
 *
 * No external geocoding API is called — this is a static, embedded
 * lookup (major US metro areas, falling back to state centroids), which
 * keeps it free, fast, and testable without network access. It is
 * intentionally approximate: a city-center or state-center point, not a
 * street address. That's disclosed to the family in the UI wherever a
 * resolved (non-exact) distance is shown.
 */
class CityCoordinateResolver
{
    /**
     * Major US metro areas, city name (lowercase) => [state => [lat, lng]].
     * Covers the ~120 largest US cities/metro areas by population, which
     * is enough to resolve realistic seed data and the large majority of
     * real family/agency locations. Anything not listed falls back to
     * its state's centroid.
     */
    private const CITY_COORDINATES = [
        'new york' => ['NY' => [40.7128, -74.0060]],
        'los angeles' => ['CA' => [34.0522, -118.2437]],
        'chicago' => ['IL' => [41.8781, -87.6298]],
        'houston' => ['TX' => [29.7604, -95.3698]],
        'phoenix' => ['AZ' => [33.4484, -112.0740]],
        'philadelphia' => ['PA' => [39.9526, -75.1652]],
        'san antonio' => ['TX' => [29.4241, -98.4936]],
        'san diego' => ['CA' => [32.7157, -117.1611]],
        'dallas' => ['TX' => [32.7767, -96.7970]],
        'austin' => ['TX' => [30.2672, -97.7431]],
        'san jose' => ['CA' => [37.3382, -121.8863]],
        'fort worth' => ['TX' => [32.7555, -97.3308]],
        'jacksonville' => ['FL' => [30.3322, -81.6557]],
        'columbus' => ['OH' => [39.9612, -82.9988]],
        'charlotte' => ['NC' => [35.2271, -80.8431]],
        'san francisco' => ['CA' => [37.7749, -122.4194]],
        'indianapolis' => ['IN' => [39.7684, -86.1581]],
        'seattle' => ['WA' => [47.6062, -122.3321]],
        'denver' => ['CO' => [39.7392, -104.9903]],
        'washington' => ['DC' => [38.9072, -77.0369]],
        'boston' => ['MA' => [42.3601, -71.0589]],
        'el paso' => ['TX' => [31.7619, -106.4850]],
        'nashville' => ['TN' => [36.1627, -86.7816]],
        'detroit' => ['MI' => [42.3314, -83.0458]],
        'oklahoma city' => ['OK' => [35.4676, -97.5164]],
        'portland' => ['OR' => [45.5152, -122.6784]],
        'las vegas' => ['NV' => [36.1699, -115.1398]],
        'memphis' => ['TN' => [35.1495, -90.0490]],
        'louisville' => ['KY' => [38.2527, -85.7585]],
        'baltimore' => ['MD' => [39.2904, -76.6122]],
        'milwaukee' => ['WI' => [43.0389, -87.9065]],
        'albuquerque' => ['NM' => [35.0844, -106.6504]],
        'tucson' => ['AZ' => [32.2226, -110.9747]],
        'fresno' => ['CA' => [36.7378, -119.7871]],
        'sacramento' => ['CA' => [38.5816, -121.4944]],
        'mesa' => ['AZ' => [33.4152, -111.8315]],
        'kansas city' => ['MO' => [39.0997, -94.5786]],
        'atlanta' => ['GA' => [33.7490, -84.3880]],
        'omaha' => ['NE' => [41.2565, -95.9345]],
        'colorado springs' => ['CO' => [38.8339, -104.8214]],
        'raleigh' => ['NC' => [35.7796, -78.6382]],
        'miami' => ['FL' => [25.7617, -80.1918]],
        'long beach' => ['CA' => [33.7701, -118.1937]],
        'virginia beach' => ['VA' => [36.8529, -75.9780]],
        'oakland' => ['CA' => [37.8044, -122.2712]],
        'minneapolis' => ['MN' => [44.9778, -93.2650]],
        'tulsa' => ['OK' => [36.1540, -95.9928]],
        'tampa' => ['FL' => [27.9506, -82.4572]],
        'arlington' => ['TX' => [32.7357, -97.1081]],
        'new orleans' => ['LA' => [29.9511, -90.0715]],
        'wichita' => ['KS' => [37.6872, -97.3301]],
        'cleveland' => ['OH' => [41.4993, -81.6944]],
        'bakersfield' => ['CA' => [35.3733, -119.0187]],
        'aurora' => ['CO' => [39.7294, -104.8319]],
        'anaheim' => ['CA' => [33.8366, -117.9143]],
        'honolulu' => ['HI' => [21.3069, -157.8583]],
        'santa ana' => ['CA' => [33.7455, -117.8677]],
        'riverside' => ['CA' => [33.9806, -117.3755]],
        'corpus christi' => ['TX' => [27.8006, -97.3964]],
        'lexington' => ['KY' => [38.0406, -84.5037]],
        'stockton' => ['CA' => [37.9577, -121.2908]],
        'st. louis' => ['MO' => [38.6270, -90.1994]],
        'saint louis' => ['MO' => [38.6270, -90.1994]],
        'pittsburgh' => ['PA' => [40.4406, -79.9959]],
        'cincinnati' => ['OH' => [39.1031, -84.5120]],
        'anchorage' => ['AK' => [61.2181, -149.9003]],
        'plano' => ['TX' => [33.0198, -96.6989]],
        'orlando' => ['FL' => [28.5383, -81.3792]],
        'irvine' => ['CA' => [33.6846, -117.8265]],
        'newark' => ['NJ' => [40.7357, -74.1724]],
        'durham' => ['NC' => [35.9940, -78.8986]],
        'st. paul' => ['MN' => [44.9537, -93.0900]],
        'saint paul' => ['MN' => [44.9537, -93.0900]],
        'buffalo' => ['NY' => [42.8864, -78.8784]],
        'jersey city' => ['NJ' => [40.7178, -74.0431]],
        'fort wayne' => ['IN' => [41.0793, -85.1394]],
        'chandler' => ['AZ' => [33.3062, -111.8413]],
        'st. petersburg' => ['FL' => [27.7676, -82.6403]],
        'laredo' => ['TX' => [27.5306, -99.4803]],
        'madison' => ['WI' => [43.0731, -89.4012]],
        'lubbock' => ['TX' => [33.5779, -101.8552]],
        'chula vista' => ['CA' => [32.6401, -117.0842]],
        'reno' => ['NV' => [39.5296, -119.8138]],
        'gilbert' => ['AZ' => [33.3528, -111.7890]],
        'baton rouge' => ['LA' => [30.4515, -91.1871]],
        'irving' => ['TX' => [32.8140, -96.9489]],
        'scottsdale' => ['AZ' => [33.4942, -111.9261]],
        'north las vegas' => ['NV' => [36.1989, -115.1175]],
        'fremont' => ['CA' => [37.5485, -121.9886]],
        'boise' => ['ID' => [43.6150, -116.2023]],
        'richmond' => ['VA' => [37.5407, -77.4360]],
        'san bernardino' => ['CA' => [34.1083, -117.2898]],
        'birmingham' => ['AL' => [33.5186, -86.8104]],
        'spokane' => ['WA' => [47.6588, -117.4260]],
        'rochester' => ['NY' => [43.1566, -77.6088]],
        'des moines' => ['IA' => [41.5868, -93.6250]],
        'modesto' => ['CA' => [37.6391, -120.9969]],
        'fayetteville' => ['NC' => [35.0527, -78.8784]],
        'tacoma' => ['WA' => [47.2529, -122.4443]],
        'oxnard' => ['CA' => [34.1975, -119.1771]],
        'fontana' => ['CA' => [34.0922, -117.4350]],
        'columbus' => ['GA' => [32.4610, -84.9877]],
        'montgomery' => ['AL' => [32.3792, -86.3077]],
        'moreno valley' => ['CA' => [33.9425, -117.2297]],
        'shreveport' => ['LA' => [32.5252, -93.7502]],
        'yonkers' => ['NY' => [40.9312, -73.8987]],
        'akron' => ['OH' => [41.0814, -81.5190]],
        'huntington beach' => ['CA' => [33.6603, -117.9992]],
        'little rock' => ['AR' => [34.7465, -92.2896]],
        'augusta' => ['GA' => [33.4735, -82.0105]],
        'amarillo' => ['TX' => [35.2220, -101.8313]],
        'glendale' => ['AZ' => [33.5387, -112.1860]],
        'mobile' => ['AL' => [30.6954, -88.0399]],
        'grand rapids' => ['MI' => [42.9634, -85.6681]],
        'salt lake city' => ['UT' => [40.7608, -111.8910]],
        'tallahassee' => ['FL' => [30.4383, -84.2807]],
        'huntsville' => ['AL' => [34.7304, -86.5861]],
        'grand prairie' => ['TX' => [32.7459, -96.9978]],
        'knoxville' => ['TN' => [35.9606, -83.9207]],
        'worcester' => ['MA' => [42.2626, -71.8023]],
        'newport news' => ['VA' => [37.0871, -76.4730]],
        'brownsville' => ['TX' => [25.9018, -97.4975]],
        'overland park' => ['KS' => [38.9822, -94.6708]],
        'santa clarita' => ['CA' => [34.3917, -118.5426]],
        'providence' => ['RI' => [41.8240, -71.4128]],
        'garden grove' => ['CA' => [33.7739, -117.9414]],
        'chattanooga' => ['TN' => [35.0456, -85.3097]],
        'oceanside' => ['CA' => [33.1959, -117.3795]],
        'jackson' => ['MS' => [32.2988, -90.1848]],
        'fort lauderdale' => ['FL' => [26.1224, -80.1373]],
        'santa rosa' => ['CA' => [38.4404, -122.7141]],
        'rancho cucamonga' => ['CA' => [34.1064, -117.5931]],
        'port st. lucie' => ['FL' => [27.2939, -80.3501]],
        'ontario' => ['CA' => [34.0633, -117.6509]],
        'vancouver' => ['WA' => [45.6387, -122.6615]],
        'tempe' => ['AZ' => [33.4255, -111.9400]],
        'springfield' => ['MO' => [37.2090, -93.2923]],
        'lancaster' => ['CA' => [34.6868, -118.1542]],
        'eugene' => ['OR' => [44.0521, -123.0868]],
        'pembroke pines' => ['FL' => [26.0078, -80.2963]],
        'salem' => ['OR' => [44.9429, -123.0351]],
        'cape coral' => ['FL' => [26.5629, -81.9495]],
        'peoria' => ['AZ' => [33.5806, -112.2374]],
        'sioux falls' => ['SD' => [43.5446, -96.7311]],
        'springfield' => ['MA' => [42.1015, -72.5898]],
        'elk grove' => ['CA' => [38.4088, -121.3716]],
        'rockford' => ['IL' => [42.2711, -89.0940]],
        'palmdale' => ['CA' => [34.5794, -118.1165]],
        'corona' => ['CA' => [33.8753, -117.5664]],
        'salinas' => ['CA' => [36.6777, -121.6555]],
        'pomona' => ['CA' => [34.0551, -117.7500]],
        'pasadena' => ['CA' => [34.1478, -118.1445]],
        'joliet' => ['IL' => [41.5250, -88.0817]],
        'paterson' => ['NJ' => [40.9168, -74.1718]],
        'kansas city' => ['KS' => [39.1147, -94.6275]],
        'torrance' => ['CA' => [33.8358, -118.3406]],
        'bridgeport' => ['CT' => [41.1865, -73.1952]],
        'hayward' => ['CA' => [37.6688, -122.0808]],
        'lakewood' => ['CO' => [39.7047, -105.0814]],
        'hollywood' => ['FL' => [26.0112, -80.1495]],
        'paradise' => ['NV' => [36.0908, -115.1367]],
        'naperville' => ['IL' => [41.7508, -88.1535]],
        'syracuse' => ['NY' => [43.0481, -76.1474]],
        'mesquite' => ['TX' => [32.7668, -96.5992]],
        'dayton' => ['OH' => [39.7589, -84.1916]],
        'savannah' => ['GA' => [32.0809, -81.0912]],
        'clarksville' => ['TN' => [36.5298, -87.3595]],
        'orange' => ['CA' => [33.7879, -117.8531]],
        'fullerton' => ['CA' => [33.8704, -117.9242]],
        'killeen' => ['TX' => [31.1171, -97.7278]],
        'frisco' => ['TX' => [33.1507, -96.8236]],
        'mckinney' => ['TX' => [33.1972, -96.6398]],
        'flint' => ['MI' => [43.0125, -83.6875]],
    ];

    /**
     * State abbreviation => [lat, lng] centroid, covering all 50 states +
     * DC. Used whenever the city isn't in the metro list above, or when
     * only a state was provided.
     */
    private const STATE_CENTROIDS = [
        'AL' => [32.806671, -86.791130], 'AK' => [61.370716, -152.404419], 'AZ' => [33.729759, -111.431221],
        'AR' => [34.969704, -92.373123], 'CA' => [36.116203, -119.681564], 'CO' => [39.059811, -105.311104],
        'CT' => [41.597782, -72.755371], 'DE' => [39.318523, -75.507141], 'DC' => [38.897438, -77.026817],
        'FL' => [27.766279, -81.686783], 'GA' => [33.040619, -83.643074], 'HI' => [21.094318, -157.498337],
        'ID' => [44.240459, -114.478828], 'IL' => [40.349457, -88.986137], 'IN' => [39.849426, -86.258278],
        'IA' => [42.011539, -93.210526], 'KS' => [38.526600, -96.726486], 'KY' => [37.668140, -84.670067],
        'LA' => [31.169546, -91.867805], 'ME' => [44.693947, -69.381927], 'MD' => [39.063946, -76.802101],
        'MA' => [42.230171, -71.530106], 'MI' => [43.326618, -84.536095], 'MN' => [45.694454, -93.900192],
        'MS' => [32.741646, -89.678696], 'MO' => [38.456085, -92.288368], 'MT' => [46.921925, -110.454353],
        'NE' => [41.125370, -98.268082], 'NV' => [38.313515, -117.055374], 'NH' => [43.452492, -71.563896],
        'NJ' => [40.298904, -74.521011], 'NM' => [34.840515, -106.248482], 'NY' => [42.165726, -74.948051],
        'NC' => [35.630066, -79.806419], 'ND' => [47.528912, -99.784012], 'OH' => [40.388783, -82.764915],
        'OK' => [35.565342, -96.928917], 'OR' => [44.572021, -122.070938], 'PA' => [40.590752, -77.209755],
        'RI' => [41.680893, -71.511780], 'SC' => [33.856892, -80.945007], 'SD' => [44.299782, -99.438828],
        'TN' => [35.747845, -86.692345], 'TX' => [31.054487, -97.563461], 'UT' => [40.150032, -111.862434],
        'VT' => [44.045876, -72.710686], 'VA' => [37.769337, -78.169968], 'WA' => [47.400902, -121.490494],
        'WV' => [38.491226, -80.954453], 'WI' => [44.268543, -89.616508], 'WY' => [42.755966, -107.302490],
    ];

    /**
     * @return array{lat: float, lng: float, exact: bool}|null exact=false
     *  means this came from a state centroid (city not recognized), not
     *  the more precise city-level table.
     */
    public function resolve(?string $city, ?string $state): ?array
    {
        if (!$state) {
            return null;
        }

        $state = strtoupper(trim($state));
        $cityKey = $city ? mb_strtolower(trim($city)) : null;

        if ($cityKey && isset(self::CITY_COORDINATES[$cityKey][$state])) {
            [$lat, $lng] = self::CITY_COORDINATES[$cityKey][$state];
            return ['lat' => $lat, 'lng' => $lng, 'exact' => true];
        }

        if (isset(self::STATE_CENTROIDS[$state])) {
            [$lat, $lng] = self::STATE_CENTROIDS[$state];
            return ['lat' => $lat, 'lng' => $lng, 'exact' => false];
        }

        return null;
    }
}
