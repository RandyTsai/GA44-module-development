<?php

require_once '../../vendor/autoload.php';
use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Filter;
use Google\Analytics\Data\V1beta\FilterExpression;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\OrderBy;


/**
 * Class GAnalytics4
 * *****IMPORTANT
 * before using this class, remember to
 * 1. change $property_id to customer's GA4 propertyID
 * 2. go to GCP to get a credential file(json) and setup properly
 * more explanation, refer to https://developers.google.com/analytics/devguides/reporting/data/v1/quickstart-client-libraries
 */
class GAnalytics4{


    public $startDate;
    public $endDate;
    public static $nodata;

    private $property_id = GA4_PROPERTY_ID;  // set GA4 propertyID
    private $credential_path = GA4_CREDENTIAL_PATH;
    protected $client;
    protected $sub_len = 15 ;  // substring length for dimension name
    protected $report = [
        'overview-recently' => [
            'label' => 'Overview',  // words to show and translate in page aside
            'title' => 'Recently visits',  // title of the report to show in page
            'dimensions' => ['date'],
            'metrics' => ['sessions'],
            'sort' => 'date',
            'desc' => false,
            'realtime' => false,
            'visible' => false,
            'allowed_filter' => true
        ],
        'currently-visit' => [
            'label' => 'Currently visit',
            'title' => 'How many visitors currently on my site?',
            'dimensions' => ['minutesAgo'],
            'metrics' => ['activeUsers'],
            'sort' => 'minutesAgo',
            'desc' => true,
            'realtime' => true,
            'visible' => true,
            'allowed_filter' => true
        ],
        'recently-visit' => [
            'label' => 'Recently visit',
            'title' => 'How many visitors visit my site?',
            'dimensions' => ['date'],
            'metrics' => ['sessions','screenPageViewsPerSession','averageSessionDuration'],
            'sort' => 'date',
            'desc' => false,
            'realtime' => false,
            'visible' => true,
            'allowed_filter' => false
        ],
        'visiting-time' => [
            'label' => 'Visiting time',
            'title' => 'When are visitors visiting the site?',
            'dimensions' => ['hour'],
            'metrics' => ['sessions','screenPageViews'],
            'sort' => 'hour',
            'desc' => false,
            'realtime' => false,
            'visible' => true,
            'allowed_filter' => false
        ],
        'top-visit-page' => [
            'label' => 'Top visit page',
            'title' => 'Which are the top viewing pages?',
            'dimensions' => ['pageTitle','pagePathPlusQueryString'],
            'metrics' => ['screenPageViews'],
            'sort' => 'screenPageViews',
            'desc' => true,
            'realtime' => false,
            'visible' => true,
            'allowed_filter' => true
        ],
        'landing-page' => [
            'label' => 'Landing page',
            'title' => 'From which page the visitor enters the site?',
            'dimensions' => ['pageTitle','landingPage'],
            'metrics' => ['sessions'],
            'sort' => 'sessions',
            'desc' => true,
            'realtime' => false,
            'visible' => true,
            'allowed_filter' => false
        ],
        'guest' => [
            'label' => 'Visitors',
            'title' => 'How many new visitors and returning visitors?',
            'dimensions' => ['date'],
            'metrics' => ['totalUsers','newUsers'],
            'sort' => 'date',
            'desc' => true,
            'realtime' => false,
            'visible' => true,
            'allowed_filter' => false
        ],
        'source-link' => [
            'label' => 'Source link',
            'title' => 'What are referrence page bring visitors to my site?',
            'dimensions' => ['firstUserSource'],
            'metrics' => ['sessions','screenPageViews','averageSessionDuration'],
            'sort' => 'sessions',
            'desc' => true,
            'realtime' => false,
            'visible' => true,
            'allowed_filter' => false
        ],
        'country' => [
            'label' => 'Country',
            'title' => 'Where are visitors come from?',
            'dimensions' => ['country'],
            'metrics' => ['sessions','screenPageViews','averageSessionDuration'],
            'sort' => 'sessions',
            'desc' => true,
            'realtime' => false,
            'visible' => true,
            'allowed_filter' => false
        ],
        'language' => [
            'label' => 'Language',
            'title' => 'What are language of visitors browser?',
            'dimensions' => ['language'],
            'metrics' => ['sessions','screenPageViews','averageSessionDuration'],
            'sort' => 'sessions',
            'desc' => true,
            'realtime' => false,
            'visible' => true,
            'allowed_filter' => false
        ],
        'browser' => [
            'label' => 'Browser',
            'title' => 'What browsers visitors to my site?',
            'dimensions' => ['browser'],
            'metrics' => ['sessions','screenPageViews','averageSessionDuration'],
            'sort' => 'sessions',
            'desc' => true,
            'realtime' => false,
            'visible' => true,
            'allowed_filter' => false
        ],
        'operating-system' => [
            'label' => 'Operating system',
            'title' => 'What operation systems visitors to my site?',
            'dimensions' => ['operatingSystem','operatingSystemVersion'],
            'metrics' => ['sessions','screenPageViews','averageSessionDuration'],
            'sort' => 'sessions',
            'desc' => true,
            'realtime' => false,
            'visible' => true,
            'allowed_filter' => false
        ],
        'screen-resolution' => [
            'label' => 'Screen resolution',
            'title' => 'What screen resolutions visitors to my site?',
            'dimensions' => ['screenResolution'],
            'metrics' => ['sessions','screenPageViews','averageSessionDuration'],
            'sort' => 'sessions',
            'desc' => true,
            'realtime' => false,
            'visible' => true,
            'allowed_filter' => false
        ]
    ];



    /**
     * GAnalytics4 constructor.
     */
    function __construct(){
        putenv("GOOGLE_APPLICATION_CREDENTIALS=".$this->credential_path);  // set credential file
        $this->client = new BetaAnalyticsDataClient();
    }


    public function getAllReportAttributes(){
        return $this->report;
    }

    /**
     * initial run the GA4 class by judging it's a single report or 4reports in overview page
     * @param $report_name,  e.g.'overview-recently'
     * @param $from, data YYYY-MM-DD or
     * @param $to, date
     * @return array
     */
    public function run( $report_name, $from, $to, $filter ){


        if(isset($report_name) && ($report_name != '') && ($report_name != 'overview-recently')){

            // fetch single report data
            $result =  $this->getReport($report_name, $from, $to, $filter);
            $current_report = $this->report[$report_name];

            // output
            $table_html = $this->outputTable($current_report['dimensions'], $current_report['metrics'], $result);  // for table
            $data_array = $this->outputArray( $result);  // for visualization
            return[
             'html'=>$table_html,
             'array'=>$data_array
            ];



        }else{

            // fetch multiple report data
            $report =  $this->report;
            $result =  $this->getReport('overview-recently', $from, $to, $filter);
            $result_visit =  $this->getReport('currently-visit', $from, $to, $filter);
            $result_country =  $this->getReport('country', $from, $to, $filter);
            $result_page =  $this->getReport('top-visit-page', $from, $to, $filter);

            // output
            $result_array = $this->outputArray($result);
            $result_visit_array = $this->outputArray($result_visit);
            $result_country_table = $this->outputArray( $result_country);
            $result_page_table  = $this->outputArray( $result_page);

            return[
                'recently'=>$result_array,
                'currently'=>$result_visit_array,
                'country'=>$result_country_table,
                'top-visit'=>$result_page_table
            ];

        }

    }





    /**
     * get report data from google, the response data is a complex obj+arrays, not being well re-processed
     */
    public function getReport($report_name, $from, $to, $filter = null){


        $report_attrs = $this->report[$report_name];
        $filter = trim($filter);


        // new all dimension objs
        $dimensions = [];
        foreach( $report_attrs['dimensions'] as $d){
            $dimensions[] = new Dimension(['name' => $d]);
        }

        // new all metric objs
        $metrics = [];
        foreach( $report_attrs['metrics'] as $m){
            $metrics[] = new Metric(['name' => $m]);
        }

        $options = [
            'property' => 'properties/' . $this->property_id,
            'dateRanges' => [
                new DateRange([
                    'start_date' => $from,
                    'end_date' => $to,
                ]),
            ],
            'dimensions' => $dimensions,
            'metrics' => $metrics,
            'orderBys' => [
                new OrderBy([
                    'dimension' => new OrderBy\DimensionOrderBy([
                        'dimension_name' => $report_attrs['sort'],
                        'order_type' => in_array($report_attrs['sort'], ['hour']) ? OrderBy\DimensionOrderBy\OrderType::NUMERIC : OrderBy\DimensionOrderBy\OrderType::ALPHANUMERIC
                    ]),
                    'desc' => $report_attrs['desc'],
                ]),
            ],
        ];

        // judge if a realtime report, then make an API call.
        if($report_attrs['realtime']){
            $response_data = $this->client->runRealtimeReport($options);
        }else{
            // apply filter
            if ($report_attrs['allowed_filter']) {
                $options['dimensionFilter'] = new FilterExpression([
                    'filter' => new Filter([
                        'field_name' => 'pagePathPlusQueryString',
                        'string_filter' => new Filter\StringFilter([
                            'match_type' => Filter\StringFilter\MatchType::CONTAINS,
                            'value' => $filter,
                            'case_sensitive' => false
                        ])
                    ])
                ]);
            }
            $response_data = $this->client->runReport($options);
        }

        return $response_data;

    }



    /**
     * view Report data by integrating with HTML
     * @param string $caption
     * @param $dimensions
     * @param $metrics
     * @param $report_data
     * @return string
     */
    public function outputTable($dimensions , $metrics, $report_data){


        // titles of a row - begin
        $titles = array_merge($dimensions, $metrics);
        $html = '';
        $html .= '<thead>';
        $html .= '<tr>';

        foreach ($titles as $t){
            $html .= '<th>'.$t.'</th>';
        }
        $html .='</tr>';
        $html .= '</thead>';
        // titles of a row - end

        // values of a row - begin
        $html .= '<tbody>';
        foreach ($report_data->getRows() as $row) {
            $html .= '<tr>';
            foreach($row->getDimensionValues() as $dd){

                // substring if the dimension name str too long
                $str = $dd->getValue();
                $str = (mb_strlen($str)> $this->sub_len)? mb_substr($str,0,$this->sub_len).'...' : $str;

                $html .='<td>'.$str.'</td>';
            }

            foreach($row->getMetricValues() as $mm){
                $html .='<td>'.round($mm->getValue(),3).'</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        // values of a row - end


        return $html;

    }

    public function outputArray($report_data){

        $d_values = array(array());
        $m_values = array(array());
        foreach ($report_data->getRows() as $row) {
            foreach($row->getDimensionValues() as $k => $dd){
                // substring if the dimension name str too long
                $str = $dd->getValue();
                $str = (mb_strlen($str)> $this->sub_len)? mb_substr($str,0, $this->sub_len).'...' : $str;

                $d_values[$k][] = $str;
            }

            foreach($row->getMetricValues() as $k => $mm){
                $m_values[$k][] = round($mm->getValue(), 3);
            }
        }

        return ['dimensions' => $d_values, 'metrics'=> $m_values];

    }



}










