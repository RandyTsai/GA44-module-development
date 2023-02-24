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
    public $filter;

    protected $property_id ;  // default set in config
    protected $credential_path ;  // default set in config
    protected $sub_len_table = 25 ;  // substring length for dimension name
    protected $sub_len_chart = 15;
    protected $report = [
        'overview-recently' => [
            'label' => 'Overview',  // words to show and translate in page aside
            'title' => 'Recently visits',  // title of the report to show in page
            'dimensions' => ['date'],
            'metrics' => ['sessions'],
            'sort' => 'date',
            'desc' => false,
            'realtime' => false,
            'visible' => true,
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
            'desc' => false,
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
    protected $additional_table_row = [];
    protected $cur_dimensions;
    protected $cur_metrics;
    protected $sum_metric_figure = [];


    private $client;



    /**
     * GAnalytics4 constructor.
     */
    function __construct($p_id =GA4_PROPERTY_ID , $c_path = GA4_CREDENTIAL_PATH){
        $this->property_id = $p_id;
        $this->credential_path = $c_path;

        putenv("GOOGLE_APPLICATION_CREDENTIALS=".$this->credential_path);  // set credential file
        $this->client = new BetaAnalyticsDataClient();
    }

    public function getAllReportAttributes(){
        return $this->report;
    }

    public function setSubLenTable( $max_len_num ){
        $this->sub_len_table = $max_len_num;
    }
    public function setSubLenChart( $max_len_num ){
        $this->sub_len_chart = $max_len_num;
    }



    /**
     * initial run the GA4 class by judging it's a single report or 4reports in overview page
     * @param $report_name,  e.g.'overview-recently'
     * @param $from, data YYYY-MM-DD or
     * @param $to, date
     * @return array
     */
    public function run( $report_name, $from, $to, $filter = null ){

        $this->startDate = $from;
        $this->endDate = $to;
        $this->filter = $filter;
        $this->cur_dimensions = $this->report[$report_name]['dimensions'];
        $this->cur_metrics = $this->report[$report_name]['metrics'];

        $current_report = $this->report[$report_name];

        if(isset($report_name) && ($report_name != '') && ($report_name != 'overview-recently')){

            //  fetch  report data and implement cache mechanism
            $result = $this->getReport($report_name, $from, $to, $filter);

            // output
            $table_html = $this->outputTable($current_report['dimensions'], $current_report['metrics'], $result);  // for table
            $data_array = $this->outputArray( $result);  // for visualization

            return[
             'html'=>$table_html,
             'array'=>$data_array
            ];

        }else{

            //  fetch  report data and implement cache mechanism
            $result_recv =  $this->getReport('overview-recently', $from, $to, $filter );
            $result_visit =  $this->getReport('currently-visit', $from, $to, $filter );
            $result_country =  $this->getReport('country', $from, $to, $filter );
            $result_page =  $this->getReport('top-visit-page', $from, $to, $filter );

            // output
            $result_recv_arr = $this->outputArray($result_recv);
            $result_recv_html = $this->outputTable($current_report['dimensions'], $current_report['metrics'], $result_recv);
            $result_curv_arr = $this->outputArray($result_visit);
            $result_country_arr = $this->outputArray( $result_country);
            $result_page_arr  = $this->outputArray( $result_page);

            return[
                'recently_arr'=>$result_recv_arr,
                'recently_html'=>$result_recv_html,
                'currently'=>$result_curv_arr,
                'country'=>$result_country_arr,
                'top-visit'=>$result_page_arr
            ];

        }

    }



    /**
     * get report data from google, the response data is a complex obj+arrays, not being well re-processed
     * @param $report_name
     * @param $from
     * @param $to
     * @param null $filter
     * @return \Google\Analytics\Data\V1beta\RunRealtimeReportResponse|\Google\Analytics\Data\V1beta\RunReportResponse|mixed
     * @throws \Google\ApiCore\ApiException
     */
    public function getReport($report_name, $from, $to, $filter = null){


        $unique_id = $report_name.'_'.hash('md5', $from.$to.$filter);  // an unique file name since a parameter changed
        $cache_file = getcwd()."/cache/cache_".$unique_id.".txt";
        if (!file_exists(getcwd()."/cache/")) { mkdir(getcwd()."/cache/", 0777, true); }  // check folder exist and create
        $filemtime = filemtime($cache_file);  // get file last modified time


        if( file_exists($cache_file) && (time()-$filemtime <= 180)  ){

            //read from cache
            $tmp_data = file_get_contents($cache_file);
            $response_data = unserialize($tmp_data);

            return $response_data;

        }else{

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

            // write cache data
            $tmp_data = serialize($response_data);
            file_put_contents($cache_file, $tmp_data);

            return $response_data;
        }

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

                $str = $dd->getValue();

                // format the original yyyymmdd to yyyy-mm-dd
                if(strtotime($str)){ $str = date('Y-m-d',strtotime($str)); }
                // substring if the dimension name str too long
                if( mb_strlen($str) > $this->sub_len_table){
                    $str_cut = mb_substr($str,0,$this->sub_len_table).'...';
                    $html .='<td>'.$str_cut.'<span class="hover">'.$str.'</span></td>';  // store complete and sub sentence
                }else{
                    $html .='<td>'.$str.'</td>';
                }

            }

            foreach($row->getMetricValues() as $k => $mm){

                $val = round($mm->getValue(),3);
                $html .='<td>'.$val.'</td>';

                if(isset( $this->sum_metric_figure[$metrics[$k]] )){
                    $this->sum_metric_figure[$metrics[$k]] += $val;
                }else{
                    $this->sum_metric_figure[$metrics[$k]] = $val;
                }

            }
            $html .= '</tr>';

        }

        // add additional row for metric number sum
        $this->addSumData(['sessions', 'totalUsers','newUsers' ]);

        // check if there are additional rows
        if(count( $this->additional_table_row)>0){
            foreach ($this->additional_table_row as $add_row) { $html .= $add_row;}
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

                $str = $dd->getValue();
                // format the original yyyymmdd to yyyy-mm-dd
                if(strtotime($str)){ $str = date('Y-m-d',strtotime($str)); }
                // substring if the dimension name str too long
                $str = (mb_strlen($str)> $this->sub_len_chart)? mb_substr($str,0, $this->sub_len_chart).'...' : $str;

                $d_values[$k][] = $str;
            }

            foreach($row->getMetricValues() as $k => $mm){
                $m_values[$k][] = round($mm->getValue(), 3);
            }
        }

        return ['dimensions' => $d_values, 'metrics'=> $m_values];

    }


    /**
     * add row at the bottom of output table ***for the sum of metric***
     * default called by outputTable()
     * @param $metric_name , the metric name you want to sum up
     * @param $title , the title of the row
     */
    public function addSumData($metric_name = []){

        $name_appear =  array_intersect($metric_name, $this->cur_metrics);
        $html = '';

        if( !empty($name_appear) ){
            $html .= '<tr>';
            $html .= '<td class="total">Total : </td>';
            for($i=0; $i< (count($this->cur_dimensions) -1); $i++){$html .= '<td> - </td>';}

            foreach ( $this->cur_metrics as $cname){
                $tmp = '<td> - </td>';
                foreach ($name_appear as $gname){
                    if( $gname == $cname ){
                        $tmp = '<td>'.$this->sum_metric_figure[$cname].'</td>';  // replace
                    }
                }
                $html .= $tmp;
            }
            $html .= '</tr>';
        }

        if($html!=='') $this->additional_table_row[] = $html;

        return;

    }



}










