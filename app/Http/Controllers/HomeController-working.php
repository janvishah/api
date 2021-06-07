<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use vendor\autoload;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Google_Client; 
use Google_Service_Analytics;

class HomeController extends Controller
{
    public function index()
    {
        return view('admin.home');
    }

    public function getAnalyticsSummary()
    {
        $analytics = HomeController::initializeAnalytics();
        $profile = HomeController::getFirstProfileId($analytics);
        $results = HomeController::getResults($analytics, $profile);
        HomeController::printResults($results);
    }   
    function initializeAnalytics()
    {
        // Creates and returns the Analytics Reporting service object.
    
        // Use the developers console and download your service account
        // credentials in JSON format. Place them in this directory or
        // change the key file location if necessary.
        $KEY_FILE_LOCATION =  app_path('analytics/beacon-credentials.json');
    
        // Create and configure a new client object.
        $client = new Google_Client();
        $client->setApplicationName("Hello Analytics Reporting");
        $client->setAuthConfig($KEY_FILE_LOCATION);
        
        $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
        
        $analytics = new Google_Service_Analytics($client);
        return $analytics;
    }
        
    function getFirstProfileId($analytics) {
        // Get the user's first view (profile) ID.
    
        // Get the list of accounts for the authorized user.
        $accounts = $analytics->management_accounts->listManagementAccounts();
        if (count($accounts->getItems()) > 0) {
            $items = $accounts->getItems();
            $firstAccountId = $items[0]->getId();
        
            // Get the list of properties for the authorized user.
            $properties = $analytics->management_webproperties->listManagementWebproperties($firstAccountId);
        
    
            if (count($properties->getItems()) > 0) {
                $items = $properties->getItems();
                $firstPropertyId = $items[0]->getId();
        
                // Get the list of views (profiles) for the authorized user.
                $profiles = $analytics->management_profiles
                    ->listManagementProfiles($firstAccountId, $firstPropertyId);
    
                if (count($profiles->getItems()) > 0) {
                    $items = $profiles->getItems();
                    // Return the first view (profile) ID.
                    return $items[0]->getId();
        
                } else {
                    throw new Exception('No views (profiles) found for this user.');
                }
            } else {
                throw new Exception('No properties found for this user.');
            }
        } else {
            throw new Exception('No accounts found for this user.');
        }
    }
        
    function getResults($analytics, $profileId) {
        // Calls the Core Reporting API and queries for the number of sessions
        // for the last seven days.
        $res = $analytics->data_ga->get(
            'ga:' . $profileId,
            '7daysAgo',
            'today',
            'ga:sessions');
            dd($res);
            return $res;
    }
        
    function printResults($results) {
        
        // Parses the response from the Core Reporting API and prints
        // the profile name and total sessions.
        if (count($results->getRows()) > 0) {
    
            // Get the profile name.
            $profileName = $results->getProfileInfo()->getProfileName();
        
            // Get the entry for the first entry in the first row.
            $rows = $results->getRows();
            $sessions = $rows[0][0];
        
            // Print the results.
            print "First view (profile) found: $profileName\n";
            print "Total sessions: $sessions\n";
        } else {
            print "No results found.\n";
        }
    }
   
}



<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use vendor\autoload;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Google_Client; 
use Google_Service_Analytics;
use Google_Auth_AssertionCredentials;

class HomeController extends Controller
{
    public function index()
    {
        return view('admin.home');
    }

    public function getAnalyticsSummary()
    {
        
        $from_date = date("Y-m-d", strtotime('7daysAgo'));
        $to_date = date("Y-m-d",strtotime('today')) ;
        $gAData = $this->gASummary($from_date,$to_date) ;
        dd($gAData);
        return $gAData;
    }
        //to get the summary of google analytics.
    private function gASummary($date_from,$date_to) 
    {
        $service_account_email = 'starting-account-abl6d9ds8t0a@vardaam-1622443242469.iam.gserviceaccount.com';
        // Create and configure a new client object.
        $client = new \Google_Client();
        $client->setApplicationName("API");
        $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
        $analytics = new \Google_Service_Analytics($client);
        
        $path = app_path('analytics/beacon-credentials.json');
        $cred = $client->setAuthConfig($path);
       
        if($client->isAccessTokenExpired()) {
            $client->refreshTokenWithAssertion($cred);
        }
        $optParams = [
            'dimensions' => 'ga:date',
            'sort'=>'-ga:date'
        ];
        $results = $analytics->data_ga->get(
            'ga:244244700',
            $date_from,
            $date_to,
            'ga:sessions',
            $optParams
        );
        $rows = $results->getRows();
        $rows_re_align = [] ;
        foreach($rows as $key=>$row) {
            foreach($row as $k=>$d) {
                $rows_re_align[$k][$key] = $d ;
            }
        }
        
        $optParams = array(
            'dimensions' => 'rt:medium'
        );
        try {
            $results1 = $analytics->data_realtime->get(
                'ga:{View ID}',
                'rt:activeUsers',
                $optParams
            );
        // Success.
        } catch (apiServiceException $e) {
        // Handle API service exceptions.
            $error = $e->getMessage();
        }

        $active_users = $results1->totalsForAllResults ;
        return [
            'data'=> $rows_re_align ,
            'summary'=>$results->getTotalsForAllResults(),
            'active_users'=>$active_users['rt:activeUsers']
            ] ;
    }
   
}